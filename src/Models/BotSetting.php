<?php

namespace TelegramBotEssentials\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use TelegramBotEssentials\Essence\Models\Bot;

class BotSetting extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
