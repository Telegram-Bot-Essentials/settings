<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Settings\Services\Settings;

/**
 * Sensitive settings are read with decrypt(), but values saved before a key became
 * sensitive were stored as plain text, which made reading them throw "The payload is
 * invalid". Encrypts those in place. Safe to run repeatedly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_settings')) {
            return;
        }

        app(Settings::class)->encryptPlainSensitiveValues();
    }

    public function down(): void
    {
        // Not reversible on purpose: nothing should be stored as plain text again.
    }
};
