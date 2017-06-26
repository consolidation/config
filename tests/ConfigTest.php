<?php
namespace Consolidation\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigurationWithCrossFileReferences()
    {
        $config = new Config();
        $processor = new ConfigProcessor();
        $loader = new YamlConfigLoader();
        $processor->extend($loader->load(__DIR__ . '/data/config-1.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-2.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-3.yml'));

        // We must capture the sources before exporting, as export
        // dumps this information.
        $sources = $processor->sources();

        $config->import($processor->export());

        $this->assertEquals(implode(',', $config->get('m')), '3');
        $this->assertEquals($config->get('a'), 'foobarbaz');

        $this->assertEquals($sources['a'], __DIR__ . '/data/config-3.yml');
        $this->assertEquals($sources['b'], __DIR__ . '/data/config-2.yml');
        $this->assertEquals($sources['c'], __DIR__ . '/data/config-1.yml');
    }

    public function testConfigurationWithReverseOrderCrossFileReferences()
    {
        $config = new Config();
        $processor = new ConfigProcessor();
        $loader = new YamlConfigLoader();
        $processor->extend($loader->load(__DIR__ . '/data/config-3.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-2.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-1.yml'));

        $sources = $processor->sources();
        $config->import($processor->export());

        $this->assertEquals(implode(',', $config->get('m')), '1');

        if (strpos($config->get('a'), '$') !== false) {
            throw new \PHPUnit_Framework_SkippedTestError(
                'Evaluation of cross-file references in reverse order not supported.'
            );
        }
        $this->assertEquals($config->get('a'), 'foobarbaz');

        $this->assertEquals($sources['a'], __DIR__ . '/data/config-3.yml');
        $this->assertEquals($sources['b'], __DIR__ . '/data/config-2.yml');
        $this->assertEquals($sources['c'], __DIR__ . '/data/config-1.yml');
    }
}
