<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Session;

class LocaleManager
{
    public const ENGLISH = 'en';
    public const PORTUGUESE_BRAZIL = 'pt-BR';

    public static function normalize(?string $locale): string
    {
        $locale = strtolower(str_replace('_', '-', trim((string) $locale)));

        if (str_starts_with($locale, 'pt')) {
            return self::PORTUGUESE_BRAZIL;
        }

        return self::ENGLISH;
    }

    public static function toAppLocale(?string $locale): string
    {
        return self::normalize($locale) === self::PORTUGUESE_BRAZIL ? 'pt_BR' : 'en';
    }

    public static function toFrontendLocale(?string $locale): string
    {
        return self::normalize($locale) === self::PORTUGUESE_BRAZIL ? 'pt-BR' : 'en-US';
    }

    public static function toDatabaseLocale(?string $locale): string
    {
        return self::normalize($locale) === self::PORTUGUESE_BRAZIL ? 'pt-br' : 'en-us';
    }

    public static function available(): array
    {
        return [
            [
                'code' => self::ENGLISH,
                'label' => 'EN',
                'name' => 'English',
                'app' => 'en',
                'frontend' => 'en-US',
            ],
            [
                'code' => self::PORTUGUESE_BRAZIL,
                'label' => 'PT-BR',
                'name' => 'Português (Brasil)',
                'app' => 'pt_BR',
                'frontend' => 'pt-BR',
            ],
        ];
    }

    public static function resolve(?User $user = null): string
    {
        if (Session::has('locale')) {
            return self::normalize(Session::get('locale'));
        }

        if ($user?->language) {
            return self::normalize($user->language);
        }

        if (function_exists('get_domain_setting')) {
            try {
                $domainLocale = get_domain_setting('language');
                if ($domainLocale) {
                    return self::normalize($domainLocale);
                }
            } catch (\Throwable $e) {
                // Domain setting may not be available before session bootstrap.
            }
        }

        return self::ENGLISH;
    }
}
