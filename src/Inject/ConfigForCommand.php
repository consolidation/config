<?php
namespace Consolidation\Config\Inject;

use Consolidation\Config\ConfigInterface;
use Consolidation\Config\Util\ConfigFallback;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Application;

class ConfigForCommand implements EventSubscriberInterface
{
    protected $config;
    protected $application;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function setApplication(Application $application)
    {
        $this->application = $application;
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
        $this->injectConfigurationForCommand($command);

        $targetOfHelpCommand = $this->getHelpCommandTarget($command, $event->getInput());
        if ($targetOfHelpCommand) {
            $this->injectConfigurationForCommand($targetOfHelpCommand);
        }
    }

    protected function injectConfigurationForCommand($command)
    {
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

    protected function getHelpCommandTarget($command, $input)
    {
        if (($command->getName() != 'help') || (!isset($this->application))) {
            return false;
        }

        $this->fixInputForSymfony2($command, $input);

        // Symfony Console helpfully swaps 'command_name' and 'command'
        // depending on whether the user entered `help foo` or `--help foo`.
        // One of these is always `help`, and the other is the command we
        // are actually interested in.
        $nameOfCommandToDescribe = $input->getArgument('command_name');
        if ($nameOfCommandToDescribe == 'help') {
            $nameOfCommandToDescribe = $input->getArgument('command');
        }
        return $this->application->find($nameOfCommandToDescribe);
    }

    protected function fixInputForSymfony2($command, $input)
    {
        // Symfony 3.x prepares $input for us; Symfony 2.x, on the other
        // hand, passes it in prior to binding with the command definition,
        // so we have to go to a little extra work.  It may be inadvisable
        // to do these steps for commands other than 'help'.
        if (!$input->hasArgument('command_name')) {
            $command->ignoreValidationErrors();
            $command->mergeApplicationDefinition();
            $input->bind($command->getDefinition());
        }
    }
}
