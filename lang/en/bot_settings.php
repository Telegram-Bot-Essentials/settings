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
        ],
        'prompt' => '⛔️ Dear user, you have not joined the channel. Please join to continue',
        'buttons' => [
            'join' => 'Join channel ✅',
            'confirm' => 'I joined ❗️',
        ],
    ],
];

