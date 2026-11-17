<?php

namespace App\Services\Chatbot;

/**
 * Turns a reply into safe HTML for the chat bubble. Everything is escaped first; then only
 * paragraphs, "- " lists, **bold** and [text](/site-path) links are turned into markup.
 * Links to other sites are left as plain text.
 */
class ChatFormatter
{
    public static function html(string $text): string
    {
        $blocks = preg_split('/\R{2,}/u', trim(str_replace("\r\n", "\n", $text))) ?: [];
        $html = '';

        foreach ($blocks as $block) {
            $paragraph = [];
            $items = [];
            foreach (explode("\n", $block) as $line) {
                if (preg_match('/^\s*(?:[-*•]|\d+[.)])\s+(.*)$/u', $line, $m)) {
                    $items[] = '<li>'.self::inline($m[1]).'</li>';
                } elseif ($items !== [] && trim($line) !== '') {
                    $items[count($items) - 1] = substr($items[count($items) - 1], 0, -5).' '.self::inline(trim($line)).'</li>';
                } elseif (trim($line) !== '') {
                    $paragraph[] = self::inline(trim(preg_replace('/^#{1,6}\s+/', '', $line)));
                }
            }
            if ($paragraph !== []) {
                $html .= '<p>'.implode('<br>', $paragraph).'</p>';
            }
            if ($items !== []) {
                $html .= '<ul>'.implode('', $items).'</ul>';
            }
        }

        return $html;
    }

    private static function inline(string $text): string
    {
        $safe = e($text);
        $safe = preg_replace('/\*\*(.+?)\*\*/u', '<b>$1</b>', $safe) ?? $safe;

        // Same-site paths only: starts with one "/", no scheme, no "//host".
        return preg_replace_callback(
            '/\[([^\]\[]+)\]\((\/(?!\/)[A-Za-z0-9\/_\-.#?=&%]*)\)/u',
            fn (array $m) => '<a href="'.$m[2].'">'.$m[1].'</a>',
            $safe,
        ) ?? $safe;
    }
}
