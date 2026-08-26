<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Essence\Models\Bot;

return new class extends Migration
{
    private const LEGACY_COLUMN_MAP = [
        'bot_status' => 'bot.status',
        'language' => 'bot.language',
        'wallet' => 'billing.user_wallet.status',
        'zibal' => 'billing.gateways.zibal.status',
        'zibal_merchant' => 'billing.gateways.zibal.merchant',
        'pay_with_card' => 'billing.gateways.card.status',
        'pay_to_card_number' => 'billing.gateways.card.card_number',
        'pay_to_card_name' => 'billing.gateways.card.card_name',
        'transactions_chat_id' => 'billing.gateways.card.transactions_chat_id',
        'zirgozar' => 'billing.gateways.zirgozar.status',
        'zirgozar_token' => 'billing.gateways.zirgozar.token',
        'zarinpal' => 'billing.gateways.zarinpal.status',
        'zarinpal_merchant_id' => 'billing.gateways.zarinpal.merchant_id',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('bot_settings') || ! Schema::hasColumn('bot_settings', 'bot_status')) {
            return;
        }

        $legacyRows = DB::table('bot_settings')->get();
        $botCurrencies = Schema::hasColumn('bots', 'currency')
            ? DB::table('bots')->pluck('currency', 'id')
            : collect();

        Schema::rename('bot_settings', 'bot_settings_legacy');

        Schema::table('bot_settings_legacy', function (Blueprint $table) {
            $table->dropForeign('bot_settings_bot_id_foreign');
        });

        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bot::class)->constrained();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['bot_id', 'key']);
        });

        $now = now();

        foreach ($legacyRows as $row) {
            foreach (self::LEGACY_COLUMN_MAP as $column => $key) {
                if (! isset($row->{$column}) || $row->{$column} === null) {
                    continue;
                }

                DB::table('bot_settings')->insert([
                    'bot_id' => $row->bot_id,
                    'key' => $key,
                    'value' => $this->serializeValue($row->{$column}),
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ]);
            }

            if ($botCurrencies->has($row->bot_id)) {
                DB::table('bot_settings')->insert([
                    'bot_id' => $row->bot_id,
                    'key' => 'billing.currency',
                    'value' => $botCurrencies->get($row->bot_id),
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ]);
            }
        }

        foreach ($botCurrencies as $botId => $currency) {
            $exists = DB::table('bot_settings')
                ->where('bot_id', $botId)
                ->where('key', 'billing.currency')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('bot_settings')->insert([
                'bot_id' => $botId,
                'key' => 'billing.currency',
                'value' => $currency,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::drop('bot_settings_legacy');
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_settings') || Schema::hasColumn('bot_settings', 'bot_status')) {
            return;
        }

        Schema::drop('bot_settings');

        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bot::class)->unique()->constrained();
            $table->boolean('bot_status')->default(true);
            $table->char('language', 2)->default('en');
            $table->boolean('wallet')->default(false);
            $table->boolean('zibal')->default(false);
            $table->string('zibal_merchant')->nullable();
            $table->boolean('pay_with_card')->default(false);
            $table->string('pay_to_card_number')->nullable();
            $table->string('pay_to_card_name')->nullable();
            $table->bigInteger('transactions_chat_id')->nullable();
            $table->boolean('zirgozar')->default(false);
            $table->string('zirgozar_token')->nullable();
            $table->boolean('zarinpal')->default(false);
            $table->string('zarinpal_merchant_id')->nullable();
            $table->timestamps();
        });
    }

    private function serializeValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
};
