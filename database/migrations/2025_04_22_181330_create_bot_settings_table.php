<?php

use TelegramBotEssentials\Essence\Models\Bot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bot::class)->constrained();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['bot_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
