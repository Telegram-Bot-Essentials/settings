<?php

namespace TelegramBotEssentials\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use TelegramBotEssentials\Essence\Models\Bot;

class BotSettings extends Model
{
    use BelongsToTenant;

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
