<?php
return [
    'wolf-memberships.command.transfer-helloasso-files' => [
        'class' => \App\Membership\Command\TransferHelloAssoFilesCommand::class,
        'tags' => [['name' => 'command', 'command' => 'membership:transfer-helloasso-files']]
    ],
];
