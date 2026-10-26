<?php

namespace App\Services\Seo;

use App\Services\Settings;

/**
 * Page titles and meta descriptions. Staff can set a template per page type and language in the
 * admin (Settings → SEO) with placeholders like {title}, {price} or {location}; without a template
 * the page's own title is used ("Title · infokobuleti").
 */
final class MetaTemplates
{
    /** Page types with an editable template; list pages and detail pages of every module. */
    public const array PAGES = [
        'home', 'hotels', 'hotel', 'real_estate', 'property', 'classifieds', 'classified', 'jobs', 'job',
        'guide', 'place', 'events', 'event', 'news', 'article', 'business', 'page',
    ];

    /** Placeholders available in every template. */
    public const array PLACEHOLDERS = ['{title}', '{type}', '{price}', '{location}', '{date}', '{site}'];

    public const string SITE = 'infokobuleti';

    public function __construct(private Settings $settings) {}

    /**
     * @param  array<string, string|null>  $vars
     */
    public function title(?string $page, array $vars, ?string $title): string
    {
        $template = $this->template($page, 'title');

        if ($template !== null) {
            $filled = $this->fill($template, ['title' => $title] + $vars);

            if ($filled !== '') {
                return $filled;
            }
        }

        if (filled($title)) {
            return $title.' · '.self::SITE;
        }

        return $this->setting('seo.title') ?? __('meta.title');
    }

    /**
     * @param  array<string, string|null>  $vars
     */
    public function description(?string $page, array $vars, ?string $description, ?string $title = null): string
    {
        $template = $this->template($page, 'description');

        if ($template !== null) {
            $filled = $this->fill($template, ['title' => $title] + $vars);

            if ($filled !== '') {
                return $filled;
            }
        }

        return filled($description) ? (string) $description : ($this->setting('seo.description') ?? __('meta.description'));
    }

    private function template(?string $page, string $field): ?string
    {
        if ($page === null || ! in_array($page, self::PAGES, true)) {
            return null;
        }

        return $this->setting("seo.templates.{$page}.{$field}");
    }

    private function setting(string $key): ?string
    {
        $value = $this->settings->get($key.'.'.app()->getLocale());

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * Replaces the placeholders; parts around an empty placeholder are tidied ("A ·  · B" → "A · B").
     *
     * @param  array<string, string|null>  $vars
     */
    private function fill(string $template, array $vars): string
    {
        $replace = ['{site}' => self::SITE];

        foreach ($vars as $key => $value) {
            $replace['{'.$key.'}'] = trim((string) $value);
        }

        $text = strtr($template, $replace);
        $text = (string) preg_replace('/\{[a-z_]+\}/', '', $text);
        $text = (string) preg_replace('/\s*([·|,–-])\s*(?=[·|,–-]|$)/u', '', $text);
        $text = (string) preg_replace('/^\s*[·|,–-]\s*/u', '', $text);

        return trim((string) preg_replace('/\s{2,}/u', ' ', $text));
    }
}
