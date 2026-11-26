<?php

namespace App\System\Command;

use App\Core\Command\CommandInterface;
use App\Core\Command\Input;
use App\Core\Command\Output\Color;

class ClearCacheCommand implements CommandInterface
{
    public function execute(Input $input): int
    {
        $this->removeEntriesInFolder(CACHE_DIR);

        echo Color::apply('Cache cleared successfully.', 'green') . PHP_EOL;
        return 0;
    }

    private function removeEntriesInFolder(string $folder): void
    {
        $files = glob($folder . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->removeEntriesInFolder($file);
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }

    public static function getName(): string
    {
        return 'cache:clear';
    }

    public static function getDescription(): string
    {
        return 'Clears the application cache.';
    }

    public static function getArguments(): array
    {
        return [];
    }

    public static function getOptions(): array
    {
        return [];
    }
}