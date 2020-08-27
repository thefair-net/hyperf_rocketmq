<?php

declare(strict_types=1);

namespace TheFairLib;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [

            ],
            'listeners' => [

            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'queue',
                    'description' => 'The message queue.php',
                    'source' => __DIR__ . '/../publish/queue.php',
                    'destination' => BASE_PATH . '/config/autoload/queue.php',
                ],
            ],
        ];
    }
}
