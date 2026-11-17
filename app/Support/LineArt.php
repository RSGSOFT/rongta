<?php

namespace App\Support;

use App\Livewire\Admin\Settings\Index as SiteSettings;
use App\Models\ScaleModel;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Scale line drawings: the prompt that describes them, the cleaner every SVG passes through
 * before it is stored or shown, and stand-alone previews.
 *
 * A stored drawing is the inner SVG markup only (no <svg> wrapper), styled solely by the site's
 * illustration classes (i-body, i-steel, ...). Whatever the source (Claude or a file staff
 * upload), it is rebuilt from an allow-list: shapes and text only, numeric attributes only,
 * no inline colours, styles, links, scripts, event handlers or embedded images.
 */
class LineArt
{
    /** Setting that holds the prompt staff can edit; empty means the bundled default. */
    public const PROMPT_SETTING = 'lineart_prompt';

    public const DEFAULT_PROMPT_FILE = 'prompts/scale-lineart.md';

    public const VIEWBOX = '0 0 240 210';

    /** Caption keys a detail crop may use (interface strings that already exist). */
    public const CROP_KEYS = ['view.display', 'view.printer', 'view.platter', 'view.camera', 'view.screen', 'view.pan', 'view.cable'];

    /** The illustration classes of resources/css/site.css. */
    public const CLASSES = [
        'i-body', 'i-steel', 'i-neck', 'i-dark', 'i-led', 'i-lcd', 'i-lcdt', 'i-key', 'i-soft', 'i-paper', 'i-bar',
        'i-acc', 'i-leaf', 'i-line', 'i-arm', 'i-ui', 'i-uihi', 'i-aur', 'i-cone', 'i-opt', 'i-shadow',
    ];

    public const MAX_INPUT_BYTES = 300 * 1024;

    public const MAX_ELEMENTS = 3000;

    private const ELEMENTS = ['g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'text', 'tspan'];

    private const NUMBER = '/^-?(\d+(\.\d+)?|\.\d+)([eE]-?\d+)?$/';

    /** attribute => pattern its value must match */
    private const ATTRIBUTES = [
        'x' => self::NUMBER, 'y' => self::NUMBER, 'x1' => self::NUMBER, 'y1' => self::NUMBER,
        'x2' => self::NUMBER, 'y2' => self::NUMBER, 'cx' => self::NUMBER, 'cy' => self::NUMBER,
        'r' => self::NUMBER, 'rx' => self::NUMBER, 'ry' => self::NUMBER, 'width' => self::NUMBER,
        'height' => self::NUMBER, 'dx' => self::NUMBER, 'dy' => self::NUMBER,
        'stroke-width' => self::NUMBER, 'font-size' => self::NUMBER, 'letter-spacing' => self::NUMBER,
        'opacity' => self::NUMBER,
        'd' => '/^[MmLlHhVvCcSsQqTtAaZz0-9.,\s+\-eE]+$/',
        'points' => '/^[0-9.,\s+\-eE]+$/',
        'transform' => '/^(\s*(translate|scale|rotate|matrix|skewX|skewY)\(\s*[0-9.,\s+\-eE]+\)\s*)+$/',
        'stroke-dasharray' => '/^[0-9.,\s]+$/',
        'stroke-linecap' => '/^(butt|round|square)$/',
        'stroke-linejoin' => '/^(miter|round|bevel)$/',
        'text-anchor' => '/^(start|middle|end)$/',
        'font-weight' => '/^(normal|bold|[1-9]00)$/',
    ];

    /** The prompt in use: the admin's version, or the bundled default. */
    public static function prompt(): string
    {
        $custom = setting(self::PROMPT_SETTING);

        return is_string($custom) && trim($custom) !== '' ? $custom : self::defaultPrompt();
    }

    public static function defaultPrompt(): string
    {
        return (string) file_get_contents(resource_path(self::DEFAULT_PROMPT_FILE));
    }

    /** The prompt with this model's inputs written in front, ready to paste into another AI tool. */
    public static function hint(ScaleModel $model): string
    {
        $prints = match ($model->label_print) {
            'no' => 'does not print',
            default => 'prints labels or receipts (check the photos)',
        };

        return "Model name: {$model->name}\n"
            .'Use: catalog card, model page gallery and hero (draw the model page level of detail, with the two detail crops).'."\n"
            ."Printing: {$prints}.\n"
            .'Photos: attached.'."\n"
            .'Outside the Rongta Uzbekistan project, so include the class definitions in a <style> block inside the SVG.'."\n\n"
            .self::prompt();
    }

    /**
     * Clean an SVG document or fragment.
     *
     * @return array{markup: string, viewBox: string}|null null when nothing usable is left
     */
    public static function clean(string $svg): ?array
    {
        $svg = trim($svg);
        if ($svg === '' || strlen($svg) > self::MAX_INPUT_BYTES || preg_match('/<!(DOCTYPE|ENTITY)/i', $svg)) {
            return null;
        }
        if (! preg_match('/^<svg[\s>]/i', preg_replace('/^(<\?xml[^>]*\?>\s*)?(<!--.*?-->\s*)*/s', '', $svg))) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="'.self::VIEWBOX.'">'.$svg.'</svg>';
        }

        $source = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $source->loadXML($svg, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $loaded ? $source->documentElement : null;
        if (! $root instanceof DOMElement || strtolower($root->localName) !== 'svg') {
            return null;
        }

        $out = new DOMDocument;
        $wrapper = $out->createElement('g');
        $count = 0;
        foreach (iterator_to_array($root->childNodes) as $child) {
            self::copy($child, $wrapper, $out, $count);
        }
        if ($count === 0 || $count > self::MAX_ELEMENTS) {
            return null;
        }

        $markup = '';
        foreach ($wrapper->childNodes as $node) {
            $markup .= $out->saveXML($node);
        }

        return ['markup' => $markup, 'viewBox' => self::viewBox($root->getAttribute('viewBox')) ?? self::VIEWBOX];
    }

    /** A valid "minX minY width height" with positive size, normalised; null otherwise. */
    public static function viewBox(?string $value): ?string
    {
        $parts = preg_split('/[\s,]+/', trim((string) $value)) ?: [];
        if (count($parts) !== 4) {
            return null;
        }
        foreach ($parts as $part) {
            if (! preg_match(self::NUMBER, $part)) {
                return null;
            }
        }
        if ((float) $parts[2] <= 0 || (float) $parts[3] <= 0) {
            return null;
        }

        return implode(' ', array_map(fn (string $n) => (string) (0 + $n), $parts));
    }

    /**
     * Detail crops kept from a list of {viewBox, key}; at most two, valid ones only.
     *
     * @param  array<int, mixed>  $crops
     * @return list<array{viewBox: string, key: string}>
     */
    public static function crops(array $crops): array
    {
        $kept = [];
        foreach ($crops as $crop) {
            $viewBox = self::viewBox(is_array($crop) ? (string) ($crop['viewBox'] ?? '') : null);
            $key = is_array($crop) ? (string) ($crop['key'] ?? '') : '';
            if ($viewBox !== null && in_array($key, self::CROP_KEYS, true)) {
                $kept[] = ['viewBox' => $viewBox, 'key' => $key];
            }
        }

        return array_slice($kept, 0, 2);
    }

    /**
     * A stand-alone SVG with the site's illustration styles inside, for previews and downloads.
     * $night draws it as on the dark AURORA sections.
     */
    public static function standalone(string $markup, string $viewBox = self::VIEWBOX, bool $night = false): string
    {
        $content = $night ? '<g class="night">'.$markup.'</g>' : $markup;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="'.e($viewBox).'"><style>'.self::styles().'</style>'.$content.'</svg>';
    }

    /** data: URL of a stand-alone preview, for an <img> (an image never runs scripts). */
    public static function dataUrl(string $markup, string $viewBox = self::VIEWBOX, bool $night = false): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::standalone($markup, $viewBox, $night));
    }

    /** The illustration rules of the site stylesheet, with its colour variables resolved. */
    public static function styles(): string
    {
        static $css = null;
        if ($css !== null) {
            return $css;
        }

        $site = (string) @file_get_contents(resource_path('css/site.css'));
        preg_match('/:root\s*\{(.*?)\}/s', $site, $root);
        preg_match_all('/(--[a-z0-9-]+)\s*:\s*([^;]+);/i', $root[1] ?? '', $vars, PREG_SET_ORDER);
        $values = [];
        foreach ($vars as [, $name, $value]) {
            $values[$name] = trim($value);
        }
        $accent = setting('accent');
        $values['--accent'] = is_string($accent) && preg_match('/^#[0-9A-Fa-f]{6}$/', $accent) ? $accent : SiteSettings::DEFAULT_ACCENT;

        $rules = '';
        foreach (preg_split('/\R/', $site) ?: [] as $line) {
            if (preg_match('/^(\.night\s+)?\.i-[a-z]+[\s,{.]/', $line)) {
                $rules .= $line;
            }
        }

        return $css = preg_replace_callback('/var\((--[a-z0-9-]+)\)/i', fn ($m) => $values[$m[1]] ?? '#151A21', $rules) ?? '';
    }

    private static function copy(DOMNode $node, DOMElement $parent, DOMDocument $out, int &$count): void
    {
        if ($node instanceof DOMText && in_array($parent->tagName, ['text', 'tspan'], true)) {
            $text = mb_substr(trim($node->textContent), 0, 40);
            if ($text !== '') {
                $parent->appendChild($out->createTextNode($text));
            }

            return;
        }
        if (! $node instanceof DOMElement || ! in_array($node->localName, self::ELEMENTS, true)) {
            return;
        }
        if (++$count > self::MAX_ELEMENTS) {
            return;
        }

        $element = $out->createElement($node->localName);
        foreach (self::ATTRIBUTES as $name => $pattern) {
            $value = trim($node->getAttribute($name));
            if ($value !== '' && strlen($value) <= 4000 && preg_match($pattern, $value)) {
                $element->setAttribute($name, $value);
            }
        }
        $classes = array_values(array_intersect(preg_split('/\s+/', trim($node->getAttribute('class'))) ?: [], self::CLASSES));
        if ($classes) {
            $element->setAttribute('class', implode(' ', array_unique($classes)));
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            self::copy($child, $element, $out, $count);
        }
        $parent->appendChild($element);
    }
}
