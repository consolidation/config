<?php

namespace Consolidation\Config\Tests\Unit\Inject;

use Consolidation\Config\Config;
use Consolidation\Config\Inject\ConfigForCommand;
use Consolidation\Config\Tests\Helper\MyFooCommand;
use Consolidation\Config\Tests\Unit\TestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ConfigForCommandTest extends TestBase
{
    protected $config;

    protected function setUp(): void
    {
        $data = [
            // Global options
            'options' => [
                'global' => 'from-config',
            ],
            // Define some configuration settings for the options for
            // the commands my:foo and my:bar.
            'command' => [
                'my' => [
                    // commands.my.options.* apply to all my:* commands.
                    'options' => [
                        'dir' => '/etc/common',
                        'priority' => 'normal',
                    ],
                    'foo' => [
                        // commands.my.foo.options.* apply only to the my:foo command.
                        'options' => [
                            'name' => 'baz',
                        ],
                    ],
                ],
            ],
        ];

        $this->config = new Config($data);
    }

    public function testInjection()
    {
        $command = new MyFooCommand();
        $input = new StringInput('my:foo');

        [$status, $output] = $this->runCommandViaApplication($command, $input);

        $expectedOutput = <<< EOT
Enter my:foo
dir: /etc/common
name: baz
other: fish
EOT;

        $this->assertEquals(0, $status);
        $this->assertEquals($expectedOutput, $output);
    }

    public function testInjectionWithOverride()
    {
        $command = new MyFooCommand();
        $input = new StringInput('my:foo --name=Fred');

        [$status, $output] = $this->runCommandViaApplication($command, $input);

        $expectedOutput = <<< EOT
Enter my:foo
dir: /etc/common
name: Fred
other: fish
EOT;

        $this->assertEquals(0, $status);
        $this->assertEquals($expectedOutput, $output);
    }

    public function testHelpDefaultInjection()
    {
        $command = new MyFooCommand();
        $input = new StringInput('help my:foo');

        [$status, $output] = $this->runCommandViaApplication($command, $input);

        $expectedOutput = <<< EOT
What is the name of the thing we are naming [default: "baz"]
EOT;

        $this->assertEquals(0, $status);
        $this->assertStringContainsString($expectedOutput, $output);

        $expectedOutput = <<< EOT
A certain global option. [default: "from-config"]
EOT;

        $this->assertStringContainsString($expectedOutput, $output);
    }

    protected function runCommandViaApplication($command, $input)
    {
        $application = new Application('TestApplication', '0.0.0');
        $application->getDefinition()
            ->addOption(
                new InputOption('--global', null, InputOption::VALUE_REQUIRED, 'A certain global option.', 'hardcoded')
            );

        $output = new BufferedOutput();

        $configInjector = new ConfigForCommand($this->config);
        $configInjector->setApplication($application);

        $eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
        $eventDispatcher->addSubscriber($configInjector);
        $application->setDispatcher($eventDispatcher);

        $application->setAutoExit(false);
        $application->add($command);

        $statusCode = $application->run($input, $output);
        $commandOutput = trim($output->fetch());

        return [$statusCode, $commandOutput];
    }
}
