<?php
namespace Consolidation\Config;

class ConfigProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigProcessorAdd()
    {
        $config1 = [
            'c' => 'foo',
            'm' => [1],
        ];
        $config2 = [
            'b' => '${c}bar',
            'm' => [2],
        ];
        $config3 = [
            'a' => '${b}baz',
            'm' => [3],
        ];

        $processor = new ConfigProcessor();
        $processor->add($config1);
        $processor->add($config2);
        $processor->add($config3);

        $data = $processor->export();
        $this->assertEquals('foo', $data['c']);
        $this->assertEquals('foobar', $data['b']);
        $this->assertEquals('foobarbaz', $data['a']);
    }

    public function testConfiProcessorSources()
    {
        $processor = new ConfigProcessor();
        $loader = new YamlConfigLoader();
        $processor->extend($loader->load(__DIR__ . '/data/config-1.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-2.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-3.yml'));

        $sources = $processor->sources();

        $data = $processor->export();
        $this->assertEquals('foo', $data['c']);
        $this->assertEquals('foobar', $data['b']);
        $this->assertEquals('foobarbaz', $data['a']);

        $this->assertEquals('3', $data['m'][0]);

        $this->assertEquals( __DIR__ . '/data/config-3.yml', $sources['a']);
        $this->assertEquals( __DIR__ . '/data/config-2.yml', $sources['b']);
        $this->assertEquals( __DIR__ . '/data/config-1.yml', $sources['c']);
        $this->assertEquals( __DIR__ . '/data/config-3.yml', $sources['m']);
    }

    public function testConfiProcessorSourcesLoadInReverseOrder()
    {
        $processor = new ConfigProcessor();
        $loader = new YamlConfigLoader();
        $processor->extend($loader->load(__DIR__ . '/data/config-3.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-2.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-1.yml'));

        $sources = $processor->sources();

        $data = $processor->export();
        $this->assertEquals('foo', $data['c']);
        $this->assertEquals('foobar', $data['b']);
        $this->assertEquals('foobarbaz', $data['a']);

        $this->assertEquals('1', $data['m'][0]);

        $this->assertEquals( __DIR__ . '/data/config-3.yml', $sources['a']);
        $this->assertEquals( __DIR__ . '/data/config-2.yml', $sources['b']);
        $this->assertEquals( __DIR__ . '/data/config-1.yml', $sources['c']);
        $this->assertEquals( __DIR__ . '/data/config-1.yml', $sources['m']);
    }
}
