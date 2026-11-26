<?php
return [
    'app.command.clear-cache' => [
        'class' => \App\System\Command\ClearCacheCommand::class,
        'tags' => [['name' => 'command', 'command' => 'cache:clear']]
    ],
    'app.command.help' => [
        'class' => \App\System\Command\HelpCommand::class,
        'tags' => [['name' => 'command', 'command' => 'help']]
    ]
];