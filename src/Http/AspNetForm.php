<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Http;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Helper for ASP.NET WebForms pages: extracts the hidden state fields
 * (__VIEWSTATE, __VIEWSTATEGENERATOR, __EVENTVALIDATION, etc.) that must be
 * echoed back on every postback, and merges them with your own field values.
 */
final class AspNetForm
{
    /** @param array<string,string> $fields */
    private function __construct(public readonly array $fields)
    {
    }

    public static function fromHtml(string $html): self
    {
        $crawler = new Crawler($html);
        $fields = [];

        $crawler->filter('input[type="hidden"]')->each(function (Crawler $node) use (&$fields): void {
            $name = $node->attr('name');
            if ($name !== null) {
                $fields[$name] = $node->attr('value') ?? '';
            }
        });

        return new self($fields);
    }

    /**
     * Merge the page's hidden state with the values you want to submit.
     *
     * @param array<string,string> $values
     * @return array<string,string>
     */
    public function withValues(array $values): array
    {
        return array_merge($this->fields, $values);
    }

    public function has(string $name): bool
    {
        return isset($this->fields[$name]);
    }
}
