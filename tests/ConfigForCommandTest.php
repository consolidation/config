<?php
namespace Consolidation\Config\Inject;

use Consolidation\Config\Config;
use Consolidation\TestUtils\MyFooCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigForCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $config;

    protected function setUp()
    {
        $data = [
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

        list($status, $output) = $this->runCommandViaApplication($command, $input);

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

        list($status, $output) = $this->runCommandViaApplication($command, $input);

        $expectedOutput = <<< EOT
Enter my:foo
dir: /etc/common
name: Fred
other: fish
EOT;

        $this->assertEquals(0, $status);
        $this->assertEquals($expectedOutput, $output);
    }

    protected function runCommandViaApplication($command, $input)
    {
        $configInjector = new ConfigForCommand($this->config);

        $output = new BufferedOutput();
        $application = new Application('TestApplication', '0.0.0');

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
