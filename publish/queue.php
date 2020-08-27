<?php

declare(strict_types=1);

return [

    'default' => [
        'host' => env('QUEUE_HOST'),
        'app_id' => env('QUEUE_APP_ID'),
        'app_key' => env('QUEUE_APP_KEY'),
        'driver' => 'rocketmq',
        'instance_id' => env('QUEUE_INSTANCE_ID'),
        'topic' => [
            'push_topic' => 'push'
        ],
        'group_id' => [
            'push_group_id' => 'GID_push',
        ],
    ],

];
