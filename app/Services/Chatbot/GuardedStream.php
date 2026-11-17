<?php

namespace App\Services\Chatbot;

use Closure;

/**
 * Sits between Claude's text deltas and the visitor's screen, so that nothing PriceGuard
 * would block is ever displayed, not even for a moment.
 *
 * Every delta is added to the text so far and the whole text is checked. On a hit the stream
 * is closed for good and what was displayed is taken back. Otherwise only the text up to
 * HOLD_BACK characters before the end is released: an amount that arrives in pieces
 * ("Цена 5 500" then " 000 сум") stays in the held tail until the rest of it has been seen.
 * The tail itself appears with the stored answer, after the final check.
 */
class GuardedStream
{
    /** Longer than any amount PriceGuard recognises, price word included. */
    public const HOLD_BACK = 120;

    private string $text = '';

    private string $shown = '';

    private bool $blocked = false;

    /** @param Closure(string): void $emit receives the whole displayable text, replacing the previous one */
    public function __construct(private readonly Closure $emit) {}

    /** A new API round starts: its text replaces what the previous round displayed. */
    public function newRound(): void
    {
        $this->text = '';
        $this->show('');
    }

    public function push(string $delta): void
    {
        if ($this->blocked) {
            return;
        }

        $this->text .= $delta;

        if (PriceGuard::hasAmount($this->text)) {
            $this->blocked = true;
            $this->show('');

            return;
        }

        $this->show(self::releasable($this->text));
    }

    public function blocked(): bool
    {
        return $this->blocked;
    }

    /** The text minus its held-back tail, cut at the last word boundary. */
    public static function releasable(string $text): string
    {
        $length = mb_strlen($text) - self::HOLD_BACK;
        if ($length <= 0) {
            return '';
        }

        return rtrim((string) preg_replace('/\S+$/u', '', mb_substr($text, 0, $length)));
    }

    private function show(string $text): void
    {
        if ($text !== $this->shown) {
            $this->shown = $text;
            ($this->emit)($text);
        }
    }
}
