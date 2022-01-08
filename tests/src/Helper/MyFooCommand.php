<?php

namespace Consolidation\Config\Tests\Helper;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MyFooCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('my:foo')
            ->setDescription('My foo command.')
            ->setHelp('This command tests command option injection by echoing its options')
            ->addOption(
                'other',
                null,
                InputOption::VALUE_REQUIRED,
                'Some other option',
                'fish'
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                'What is the name of the thing we are naming',
                'George'
            )
            ->addOption(
                'dir',
                null,
                InputOption::VALUE_REQUIRED,
                'What is the base directory to use for this command',
                '/default/path'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Enter my:foo');
        $output->writeln('dir: ' . $input->getOption('dir'));
        $output->writeln('name: ' . $input->getOption('name'));
        $output->writeln('other: ' . $input->getOption('other'));

        return 0;
    }
}
