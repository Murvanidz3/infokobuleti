<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Whitelist cleaner for rich text written in the admin (articles, place descriptions, pages):
 * keeps basic formatting and safe links, drops everything else (scripts, styles, event handlers).
 */
final class SafeHtml
{
    private const array TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a', 'hr', 'figure', 'figcaption', 'img'];

    private const array DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math', 'form', 'textarea', 'select', 'button'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('root');

        if ($root === null) {
            return e(strip_tags($html));
        }

        self::walk($root);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                    $child->parentNode?->removeChild($child);

                    continue;
                }

                self::walk($child);

                if (! in_array($tag, self::TAGS, true)) {
                    // Unknown wrapper (div, span, font…): keep its content, lose the tag.
                    while ($child->firstChild !== null) {
                        $child->parentNode?->insertBefore($child->firstChild, $child);
                    }

                    $child->parentNode?->removeChild($child);

                    continue;
                }

                self::cleanAttributes($child, $tag);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $child->parentNode?->removeChild($child);
            }
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = match ($tag) {
            'a' => ['href'],
            'img' => ['src', 'alt'],
            default => [],
        };

        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttribute($attribute->name);
            }
        }

        foreach (['href', 'src'] as $name) {
            if ($element->hasAttribute($name) && preg_match('#^(https?://|/|mailto:|tel:)#i', trim($element->getAttribute($name))) !== 1) {
                $element->removeAttribute($name);
            }
        }

        if ($tag === 'a' && preg_match('#^https?://#i', $element->getAttribute('href')) === 1) {
            $element->setAttribute('rel', 'noopener nofollow');
            $element->setAttribute('target', '_blank');
        }

        if ($tag === 'img') {
            $element->setAttribute('loading', 'lazy');
        }
    }
}
