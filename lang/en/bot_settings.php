<?php

return [
    'menu' => [
        'title' => 'Bot settings',
        'empty' => 'No settings found',
    ],

    'selectors' => [
        'no_options' => 'No options found',
        'prompt' => 'Pick an option:',
    ],

    'messages' => [
        'editing' => 'Editing :label…',
        'updated' => ':label updated',
        'channel_lock' => [
            'joined' => 'Thanks for joining! You can continue now.',
            'not_joined' => 'You haven\'t joined the channel yet.',
        ],
    ],

    'prompts' => [
        'enter_new_value' => 'Send the new value for :label:',
    ],

    'locale' => [
        'label' => 'Bot language',
        'description' => 'Choose which language the bot uses to reply to users.',
    ],

    'channel_lock' => [
        'label' => 'Channel lock',
        'description' => 'Require users to join a channel before they can use the bot.',
        'status' => [
            'label' => 'Status',
            'description' => 'Turn the channel-join requirement on or off.',
        ],
        'channel_id' => [
            'label' => 'Channel ID',
            'description' => 'The numeric ID of the channel users must join (e.g. -1001234567890).',
            'bot_not_admin' => 'The bot isn\'t an admin of that channel. Add it as an admin first, then try again.',
        ],
        'prompt' => '⛔️ You need to join our channel before you can continue.',
        'alerts' => [
            'no_channel' => "Channel lock is on, but no channel is set, so nobody is being asked to join.\nSet the channel in Bot Settings → Channel lock, or turn the lock off.",
            'check_failed' => "Channel lock can't check whether users joined :channel, so everyone is let through for now.\nMake sure the bot is still an admin of the channel.\n\nTelegram said: :error",
        ],
        'buttons' => [
            'join' => 'Join channel ✅',
            'confirm' => 'I joined ❗️',
        ],
    ],
];
