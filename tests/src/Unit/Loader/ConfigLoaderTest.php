<?php

namespace Consolidation\Config\Tests\Unit\Loader;

use Consolidation\Config\Loader\YamlConfigLoader;
use Consolidation\Config\Tests\Unit\TestBase;

class ConfigLoaderTest extends TestBase
{
    public function testConfigLoader()
    {
        $fixturesDir = $this->getFixturesDir();
        $loader = new YamlConfigLoader();

        // Assert that our test data exists (test the test)
        $path = "$fixturesDir/config-1.yml";
        $this->assertTrue(file_exists($path));

        $loader->load($path);

        $configFile = basename($loader->getSourceName());
        $this->assertEquals('config-1.yml', $configFile);

        // Make sure that the data we loaded contained the expected keys
        $keys = $loader->keys();
        sort($keys);
        $keysString = implode(',', $keys);
        $this->assertEquals('c,m', $keysString);

        $configData = $loader->export();
        $this->assertEquals('foo', $configData['c']);
        $this->assertEquals('1', $configData['m'][0]);
    }
}
