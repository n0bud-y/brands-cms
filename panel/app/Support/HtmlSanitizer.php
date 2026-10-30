<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Allow-list cleaner for rich-text block fields. Brand sites echo these fields
 * unescaped, so anything not explicitly allowed here never leaves the panel.
 */
final class HtmlSanitizer
{
    /** Allowed tag => allowed attributes. Other tags are unwrapped (their text is kept). */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'a' => ['href', 'title', 'target'],
        'ul' => [], 'ol' => [], 'li' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'blockquote' => [], 'code' => [], 'pre' => [],
    ];

    /** Removed together with everything inside them. */
    private const DROPPED = [
        'script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript', 'svg', 'math',
        'form', 'input', 'button', 'textarea', 'select', 'title', 'head', 'link', 'meta',
    ];

    private const BLOCK_LEVEL = ['p', 'div', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'table'];

    public static function clean(mixed $html): string
    {
        if (! is_string($html) || trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        self::walk($body);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * Returns the URL if it is relative or uses an allowed scheme, otherwise ''.
     * Blocks javascript:, data: and friends, including whitespace-obfuscated forms.
     */
    public static function safeUrl(mixed $url, bool $allowContact = false): string
    {
        if (! is_string($url)) {
            return '';
        }

        $url = trim($url);
        $probe = strtolower((string) preg_replace('/[\x00-\x20]+/', '', $url));
        if (! preg_match('/^[a-z][a-z0-9+.\-]*:/', $probe)) {
            return $url;
        }

        $allowed = $allowContact ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https'];

        return in_array(strstr($probe, ':', true), $allowed, true) ? $url : '';
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }
            if (! $child instanceof DOMElement) {
                $node->removeChild($child); // comments, processing instructions

                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROPPED, true)) {
                $node->removeChild($child);

                continue;
            }

            // Editors emit <div> for lines; keep them as paragraphs unless they wrap other blocks.
            if ($tag === 'div' && ! self::containsBlock($child)) {
                $child = self::rename($child, 'p');
                $tag = 'p';
            }

            self::walk($child);

            if (! isset(self::ALLOWED[$tag])) {
                self::unwrap($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                if (! in_array(strtolower($attr->nodeName), self::ALLOWED[$tag], true)) {
                    $child->removeAttribute($attr->nodeName);
                }
            }

            if ($tag === 'a') {
                self::cleanLink($child);
            }
        }
    }

    private static function cleanLink(DOMElement $a): void
    {
        $href = self::safeUrl($a->getAttribute('href'), true);
        $href === '' ? $a->removeAttribute('href') : $a->setAttribute('href', $href);

        if ($a->getAttribute('target') === '_blank') {
            $a->setAttribute('rel', 'noopener noreferrer');
        } else {
            $a->removeAttribute('target');
        }
    }

    private static function containsBlock(DOMElement $el): bool
    {
        foreach (self::BLOCK_LEVEL as $tag) {
            if ($el->getElementsByTagName($tag)->length > 0) {
                return true;
            }
        }

        return false;
    }

    private static function rename(DOMElement $el, string $tag): DOMElement
    {
        $new = $el->ownerDocument->createElement($tag);
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);

        return $new;
    }

    private static function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }
}
