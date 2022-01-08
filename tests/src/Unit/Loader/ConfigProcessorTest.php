<?php

namespace Consolidation\Config\Tests\Unit\Loader;

use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use Consolidation\Config\Tests\Helper\TestLoader;
use Consolidation\Config\Tests\Unit\TestBase;

class ConfigProcessorTest extends TestBase
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

    public function processorForConfigMergeTest($provideSourceNames, $useMergeBehavor = false)
    {
        $config1 = [
            'm' => [
                'x' => 'x-1',
                'y' => [
                    'r' => 'r-1',
                    's' => 's-1',
                    't' => 't-1',
                ],
                'z' => 'z-1',
                'list' => [
                    'one-a',
                    'one-b',
                ]
            ],
        ];
        $config2 = [
            'm' => [
                'w' => 'w-2',
                'y' => [
                    'q' => 'q-2',
                    's' => 's-2',
                ],
                'z' => 'z-2',
                'list' => [
                    'two-a',
                    'two-b',
                ]
            ],
        ];
        $config3 = [
            'm' => [
                'v' => 'v-3',
                'y' => [
                    't' => 't-3',
                    'u' => 'u-3',
                ],
                'z' => 'z-3',
                'list' => [
                    'three-a',
                    'three-b',
                ]
            ],
        ];

        $processor = new ConfigProcessor();
        $testLoader = new TestLoader();

        if ($useMergeBehavor) {
            $processor->useMergeStrategyForKeys($useMergeBehavor);
        }

        $testLoader->set($config1);
        $testLoader->setSourceName($provideSourceNames ? 'c-1' : '');
        $processor->extend($testLoader);

        $testLoader->set($config2);
        $testLoader->setSourceName($provideSourceNames ? 'c-2' : '');
        $processor->extend($testLoader);

        $testLoader->set($config3);
        $testLoader->setSourceName($provideSourceNames ? 'c-3' : '');
        $processor->extend($testLoader);

        return $processor;
    }

    public function testConfigProcessorMergeAssociative()
    {
        $processor = $this->processorForConfigMergeTest(false, false);
        $data = $processor->export();
        $this->assertEquals('{"m":{"x":"x-1","y":{"r":"r-1","s":"s-2","t":"t-3","q":"q-2","u":"u-3"},"z":"z-3","list":["three-a","three-b"],"w":"w-2","v":"v-3"}}', json_encode($data));
    }

    public function testConfigProcessorWithMergeBehavior()
    {
        $processor = $this->processorForConfigMergeTest(false, 'm.y');
        $data = $processor->export();
        $this->assertEquals('{"m":{"x":"x-1","y":{"r":"r-1","s":["s-1","s-2"],"t":["t-1","t-3"],"q":"q-2","u":"u-3"},"z":"z-3","list":["three-a","three-b"],"w":"w-2","v":"v-3"}}', json_encode($data));

        $processor = $this->processorForConfigMergeTest(false, 'm.list');
        $data = $processor->export();
        $this->assertEquals('{"m":{"x":"x-1","y":{"r":"r-1","s":"s-2","t":"t-3","q":"q-2","u":"u-3"},"z":"z-3","list":["one-a","one-b","two-a","two-b","three-a","three-b"],"w":"w-2","v":"v-3"}}', json_encode($data));

        $processor = $this->processorForConfigMergeTest(false, ['m.y', 'm.list']);
        $data = $processor->export();
        $this->assertEquals('{"m":{"x":"x-1","y":{"r":"r-1","s":["s-1","s-2"],"t":["t-1","t-3"],"q":"q-2","u":"u-3"},"z":"z-3","list":["one-a","one-b","two-a","two-b","three-a","three-b"],"w":"w-2","v":"v-3"}}', json_encode($data));

    }

    public function testConfigProcessorMergeAssociativeWithSourceNames()
    {
        $processor = $this->processorForConfigMergeTest(true);
        $sources = $processor->sources();
        $data = $processor->export();
        $this->assertEquals('{"m":{"x":"x-1","y":{"r":"r-1","s":"s-2","t":"t-3","q":"q-2","u":"u-3"},"z":"z-3","list":["three-a","three-b"],"w":"w-2","v":"v-3"}}', json_encode($data));
        $this->assertEquals('c-1', $sources['m']['x']);
        $this->assertEquals('c-1', $sources['m']['y']['r']);
        $this->assertEquals('c-2', $sources['m']['w']);
        $this->assertEquals('c-2', $sources['m']['y']['s']);
        $this->assertEquals('c-3', $sources['m']['z']);
        $this->assertEquals('c-3', $sources['m']['y']['u']);
    }

    public function testConfiProcessorSources()
    {
        $fixturesDir = $this->getFixturesDir();
        $processor = new ConfigProcessor();
        $loader = new YamlConfigLoader();
        $processor->extend($loader->load("$fixturesDir/config-1.yml"));
        $processor->extend($loader->load("$fixturesDir/config-2.yml"));
        $processor->extend($loader->load("$fixturesDir/config-3.yml"));

        $sources = $processor->sources();

        $data = $processor->export();
        $this->assertEquals('foo', $data['c']);
        $this->assertEquals('foobar', $data['b']);
        $this->assertEquals('foobarbaz', $data['a']);

        $this->assertEquals('3', $data['m'][0]);

        $this->assertEquals( "$fixturesDir/config-3.yml", $sources['m']);
        $this->assertEquals( "$fixturesDir/config-3.yml", $sources['a']);
        $this->assertEquals( "$fixturesDir/config-2.yml", $sources['b']);
        $this->assertEquals( "$fixturesDir/config-1.yml", $sources['c']);
    }

    public function testConfigProcessorSourcesLoadInReverseOrder()
    {
        $fixturesDir = $this->getFixturesDir();
        $processor = new ConfigProcessor();
        $loader = new YamlConfigLoader();
        $processor->extend($loader->load("$fixturesDir/config-3.yml"));
        $processor->extend($loader->load("$fixturesDir/config-2.yml"));
        $processor->extend($loader->load("$fixturesDir/config-1.yml"));

        $sources = $processor->sources();

        $data = $processor->export();
        $this->assertEquals('foo', $data['c']);
        $this->assertEquals('foobar', $data['b']);
        $this->assertEquals('foobarbaz', $data['a']);

        $this->assertEquals('1', $data['m'][0]);

        $this->assertEquals( "$fixturesDir/config-3.yml", $sources['a']);
        $this->assertEquals( "$fixturesDir/config-2.yml", $sources['b']);
        $this->assertEquals( "$fixturesDir/config-1.yml", $sources['c']);
        $this->assertEquals( "$fixturesDir/config-1.yml", $sources['m']);
    }
}
