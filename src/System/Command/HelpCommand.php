<?php

namespace App\System\Command;

use App\Core\Command\CommandInterface;
use App\Core\Command\Input;
use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;

class HelpCommand implements CommandInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function execute(Input $input): int
    {
        if ($input->arguments->has('command') && $input->arguments->get('command') !== 'help') {
            $this->renderCommandHelp($input->arguments->get('command'));
        } else {
            $this->renderGlobalHelp();
        }
        return 0;
    }

    private function renderGlobalHelp()
    {
        echo "Available commands:" . PHP_EOL;
        $serviceIds = $this->container->findByTag('command');
        foreach ($serviceIds as $serviceId) {
            $definition = $this->container->getDefinition($serviceId);
            $className = $this->getClassForCommand($definition);
            echo " - " . $className::getName() . ": " . $className::getDescription() . PHP_EOL;
        }
    }

    private function renderCommandHelp(string $commandName)
    {
        $serviceIds = $this->container->findByTag('command');
        $serviceId = null;
        foreach ($serviceIds as $id) {
            $definition = $this->container->getDefinition($id);
            foreach ($definition['tags'] as $tag) {
                if ($tag['name'] === 'command' && isset($tag['command']) && $tag['command'] === $commandName) {
                    $serviceId = $id;
                    break 2;
                }
            }
        }

        if (!$serviceId) {
            echo "Command not found: " . $commandName . PHP_EOL;
            return;
        }

        $definition = $this->container->getDefinition($serviceId);
        $className = $this->getClassForCommand($definition);
        echo "Command: " . $className::getName() . PHP_EOL;
        echo "Description: " . $className::getDescription() . PHP_EOL;
        echo "Arguments: " . implode(", ", array_keys($className::getArguments())) . PHP_EOL;
        echo "Options: " . implode(", ", array_keys($className::getOptions())) . PHP_EOL;
    }

    private function getClassForCommand(array &$definition): string
    {
        if (isset($definition['class'])) {
            return $definition['class'];
        }
        throw new \RuntimeException("Class not found in definition");
    }

    public static function getName(): string
    {
        return 'help';
    }

    public static function getDescription(): string
    {
        return 'Displays help information for available commands.';
    }

    public static function getArguments(): array
    {
        return [
            'command' => 'The command to display help for'
        ];
    }

    public static function getOptions(): array
    {
        return [];
    }
}