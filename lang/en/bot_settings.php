<?php

return [
    'menu' => [
        'title' => 'Bot Settings',
        'empty' => 'No settings found',
    ],

    'selectors' => [
        'no_options' => 'No options found',
        'prompt' => 'Select the option:',
    ],

    'messages' => [
        'editing' => 'Editing :label...',
        'updated' => ':label updated successfully',
        'channel_lock' => [
            'joined' => 'Thanks for joining! You can continue now.',
            'not_joined' => 'You have not joined the channel yet.',
        ],
    ],

    'prompts' => [
        'enter_new_value' => 'Enter new value for :label:',
    ],

    'locale' => [
        'label' => 'Bot Language',
    ],

    'channel_lock' => [
        'label' => 'Channel lock',
        'status' => [
            'label' => 'Status',
        ],
        'channel_id' => [
            'label' => 'Channel ID',
            'bot_not_admin' => 'The bot is not an admin of that channel. Please add the bot as an admin first, then try again.',
        ],
        'prompt' => '⛔️ Dear user, you have not joined the channel. Please join to continue',
        'buttons' => [
            'join' => 'Join channel ✅',
            'confirm' => 'I joined ❗️',
        ],
    ],
];

