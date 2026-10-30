<?php

namespace Website\Support;

/** Cleans rich text from the CMS editor: keeps formatting, drops scripts and event handlers. */
class Html
{
    const ALLOWED = '<p><br><strong><b><em><i><u><s><a><ul><ol><li><h2><h3><h4><blockquote><img><table><thead><tbody><tr><td><th><span><div><hr><figure><figcaption><iframe><sub><sup><pre><code>';

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }
        $html = strip_tags($html, self::ALLOWED);
        if (! class_exists(\DOMDocument::class)) {
            return preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        }
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);
        foreach ($xp->query('//*') as $el) {
            /** @var \DOMElement $el */
            foreach (iterator_to_array($el->attributes ?? []) as $attr) {
                $name = strtolower($attr->name);
                $val = trim($attr->value);
                $bad = str_starts_with($name, 'on')
                    || (in_array($name, ['href', 'src'], true) && preg_match('/^\s*(javascript|vbscript|data:text)/i', $val))
                    || ! in_array($name, ['href', 'src', 'alt', 'title', 'class', 'target', 'rel', 'width', 'height', 'colspan', 'rowspan', 'id', 'allowfullscreen', 'frameborder', 'style', 'data-list'], true);
                if ($bad) {
                    $el->removeAttribute($attr->name);
                }
            }
            if ($el->nodeName === 'iframe') {
                $src = $el->getAttribute('src');
                if (! preg_match('#^https://(www\.)?(youtube\.com|youtube-nocookie\.com|google\.com/maps|maps\.google\.com|player\.vimeo\.com)/#i', $src)) {
                    $el->parentNode->removeChild($el);
                }
            }
            if ($el->nodeName === 'a' && $el->getAttribute('target') === '_blank') {
                $el->setAttribute('rel', 'noopener');
            }
        }
        $root = $doc->getElementById('__root');
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    public static function excerpt(?string $html, int $len = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(str_replace(['</p>', '<br>'], ' ', (string) $html)))));

        return \Illuminate\Support\Str::limit($text, $len);
    }
}
