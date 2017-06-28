<?php
namespace Consolidation\Config\Inject;

use Consolidation\Config\ConfigInterface;
use Consolidation\Config\Util\ConfigFallback;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigForCommand implements EventSubscriberInterface
{
    protected $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'injectConfiguration'];
    }

    /**
     * Before a Console command runs, inject configuration settings
     * for this command into the default value of the options of
     * this command.
     *
     * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
     */
    public function injectConfiguration(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $commandName = $command->getName();
        $commandName = str_replace(':', '.', $commandName);
        $definition = $command->getDefinition();
        $options = $definition->getOptions();
        $configGroup = new ConfigFallback($this->config, $commandName, 'command.', '.options.');
        foreach ($options as $option => $inputOption) {
            $key = str_replace('.', '-', $option);
            $value = $configGroup->get($key);
            if ($value !== null) {
                $inputOption->setDefault($value);
            }
        }
    }
}
