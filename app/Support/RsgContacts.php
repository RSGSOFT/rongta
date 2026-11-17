<?php

namespace App\Support;

/**
 * RSG's phone and Telegram from the "contacts" setting, and the one place that decides
 * whether they are real. Until the owner confirms them nothing is printed anywhere.
 */
class RsgContacts
{
    /**
     * The confirmed contacts, or null. Not confirmed means: the setting is missing, its
     * is_placeholder flag is missing or on, or there is no phone number.
     *
     * @return array{phone: string, telegram: string, tel: string}|null
     *                                                                  telegram without "@" ('' when there is none); tel is the phone for a tel: link
     */
    public static function confirmed(): ?array
    {
        $contacts = setting('contacts');
        if (! is_array($contacts) || ! array_key_exists('is_placeholder', $contacts) || $contacts['is_placeholder']) {
            return null;
        }

        $phone = trim((string) ($contacts['phone'] ?? ''));
        if ($phone === '') {
            return null;
        }

        return [
            'phone' => $phone,
            'telegram' => ltrim(trim((string) ($contacts['telegram'] ?? '')), '@'),
            'tel' => (string) preg_replace('/[^\d+]/', '', $phone),
        ];
    }

    public static function areConfirmed(): bool
    {
        return self::confirmed() !== null;
    }
}
