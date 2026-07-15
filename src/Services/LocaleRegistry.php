<?php

namespace TelegramBotEssentials\Settings\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use TelegramBotEssentials\Essence\Services\TranslationScanner;

class LocaleRegistry
{
    public function __construct(
        private readonly TranslationScanner $scanner,
    ) {}

    /**
     * @return list<string>
     */
    public function supportedLocales(): array
    {
        return $this->scanner->discoverSupportedLocales();
    }

    /**
     * @return array<string, string>
     */
    public function selectOptions(): array
    {
        $options = [];
        $stats = $this->cachedStats();

        foreach ($this->supportedLocales() as $locale) {
            $label = Lang::get('tbe-settings::locales.' . $locale, [], $locale);
            $localeStats = $stats['locales'][$locale] ?? null;

            if ($localeStats) {
                $label .= sprintf(
                    ' %d/%d (%d%%)',
                    $localeStats['translated'],
                    $localeStats['total'],
                    $localeStats['percent']
                );

                if ($localeStats['percent'] === 100) {
                    $label .= ' ✅';
                }
            }

            $options[$locale] = $label;
        }

        return $options;
    }

    /**
     * @return array{base: string, total: int, locales: array<string, array{translated: int, total: int, percent: int}>, computed_at?: string}
     */
    public function cachedStats(): array
    {
        $cached = Cache::get(config('tbe-essence.translation_stats.cache_key'));

        if (is_array($cached) && isset($cached['locales'])) {
            return $cached;
        }

        $baseLocale = $this->scanner->baseLocale();
        $total = count($this->scanner->baseKeys());

        return [
            'base' => $baseLocale,
            'total' => $total,
            'locales' => $this->scanner->computeStats(),
        ];
    }
}
