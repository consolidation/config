<?php
namespace Consolidation\Config;

class ConfigProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testConfiProcessorSources()
    {
        $processor = new ConfigProcessor();
        $loader = new YamlConfigLoader();
        $processor->extend($loader->load(__DIR__ . '/data/config-1.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-2.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-3.yml'));

        $sources = $processor->sources();
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
        $this->assertEquals( __DIR__ . '/data/config-3.yml', $sources['a']);
        $this->assertEquals( __DIR__ . '/data/config-2.yml', $sources['b']);
        $this->assertEquals( __DIR__ . '/data/config-1.yml', $sources['c']);
        $this->assertEquals( __DIR__ . '/data/config-1.yml', $sources['m']);
    }
}
