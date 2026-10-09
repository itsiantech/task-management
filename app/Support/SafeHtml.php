<?php

namespace App\Support;

class SafeHtml
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><ol><ul><li><blockquote><pre><code><h1><h2><h3><h4><a><img><span><div>';

    public static function clean(?string $html): string
    {
        $html = (string) $html;

        if ($html === '') {
            return '';
        }

        $html = preg_replace('#<(script|iframe|object|embed|style|form|link|meta)[^>]*>.*?</\\1>#is', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1=$2#$2', $html) ?? '';

        return trim($html);
    }
}
