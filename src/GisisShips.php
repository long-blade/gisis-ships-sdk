<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mavroforakis\Gisis\Auth\SessionProvider;
use Mavroforakis\Gisis\Exception\AuthenticationException;
use Mavroforakis\Gisis\Exception\GisisException;
use Mavroforakis\Gisis\Http\AspNetForm;
use Mavroforakis\Gisis\Imo\ImoNumber;
use Mavroforakis\Gisis\Model\Ship;
use Mavroforakis\Gisis\Search\Condition;
use Mavroforakis\Gisis\Search\ResultsParser;
use Mavroforakis\Gisis\Search\WhereBuilder;

/**
 * Entry point for searching the GISIS public Ships database.
 *
 * Authentication is delegated to a {@see SessionProvider}; this class never
 * touches credentials or the Cloudflare Turnstile challenge — it only reuses an
 * already-authenticated browser session.
 */
final class GisisShips
{
    private const BASE_URL    = 'https://gisis.imo.org';
    private const SEARCH_PATH = '/Public/SHIPS/ShipSearch.aspx';

    private const SEARCH_BUTTON   = 'ctl00$bodyPlaceHolder$ShipSelector$btnSearch';
    private const CONDITIONS_FIELD = 'ctl00_bodyPlaceHolder_ShipSelector_shipWhereBuilder_conditionsXml';

    private Client $http;

    public function __construct(
        private readonly SessionProvider $session,
        ?Client $http = null,
    ) {
        $this->http = $http ?? new Client([
            'base_uri'        => self::BASE_URL,
            'cookies'         => $this->session->cookieJar(),
            'allow_redirects' => ['track_redirects' => true],
            'timeout'         => 30,
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (compatible; gisis-ships-sdk/0.1)',
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
        ]);
    }

    /** Exact lookup by IMO number (validated locally first). Returns null if not found. */
    public function findByImo(string $imo): ?Ship
    {
        $imoNumber = ImoNumber::fromString($imo);
        $ship = $this->search(Condition::imoIs((string) $imoNumber))[0] ?? null;

        // The grid omits the IMO column (it was the query), so stamp it back on.
        return $ship === null
            ? null
            : new Ship(
                imoNumber:       (string) $imoNumber,
                name:            $ship->name,
                flag:            $ship->flag,
                callSign:        $ship->callSign,
                mmsi:            $ship->mmsi,
                grossTonnage:    $ship->grossTonnage,
                deadweight:      $ship->deadweight,
                shipType:        $ship->shipType,
                yearOfBuild:     $ship->yearOfBuild,
                status:          $ship->status,
                registeredOwner: $ship->registeredOwner,
                extra:           $ship->extra,
            );
    }

    /**
     * Search ships by (partial) name.
     *
     * @return list<Ship>
     */
    public function findByName(string $name): array
    {
        return $this->search(Condition::nameContains($name));
    }

    /**
     * Run an arbitrary set of conditions against the ship search.
     *
     * @return list<Ship>
     */
    public function search(Condition ...$conditions): array
    {
        if ($conditions === []) {
            throw new GisisException('At least one search condition is required.');
        }

        $form = AspNetForm::fromHtml($this->get(self::SEARCH_PATH));

        $payload = $form->withValues([
            '__EVENTTARGET'         => self::SEARCH_BUTTON,
            '__EVENTARGUMENT'       => '',
            'ctl00$scriptManager'   => '',
            // The field value is itself URL-encoded once; Guzzle encodes the
            // whole body again, producing the double-encoding GISIS expects.
            self::CONDITIONS_FIELD  => rawurlencode(WhereBuilder::toXml(...$conditions)),
            'ctl00$hidGisisFormChanged' => '',
        ]);

        $body = $this->post(self::SEARCH_PATH, $payload);

        return ResultsParser::parse($body);
    }

    private function get(string $path): string
    {
        try {
            $response = $this->http->get($path);
        } catch (GuzzleException $e) {
            throw new GisisException('GISIS request failed: ' . $e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();
        $this->assertAuthenticated($body, $response->getHeaderLine('X-Guzzle-Redirect-History'));

        return $body;
    }

    /** @param array<string,string> $payload */
    private function post(string $path, array $payload): string
    {
        try {
            $response = $this->http->post($path, ['form_params' => $payload]);
        } catch (GuzzleException $e) {
            throw new GisisException('GISIS search request failed: ' . $e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();
        $this->assertAuthenticated($body, $response->getHeaderLine('X-Guzzle-Redirect-History'));

        return $body;
    }

    /** Detect the tell-tale signs that our session expired and we got bounced to login. */
    private function assertAuthenticated(string $body, string $redirectHistory): void
    {
        if (
            str_contains($redirectHistory, 'webaccounts.imo.org')
            || str_contains($body, 'turnstile')
            || str_contains($body, 'WebLogin.aspx')
        ) {
            throw new AuthenticationException(
                'GISIS session is invalid or expired. Log in again in a browser and refresh the IMOWEBACC cookie.'
            );
        }
    }
}
