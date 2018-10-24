<?php
namespace Consolidation\Config;

use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use Consolidation\Config\Util\Interpolator;

class InterpolatorTest extends \PHPUnit_Framework_TestCase
{
    public function testFindTokens()
    {
        $interpolator = new Interpolator();

        $tokens = $interpolator->findTokens('This is a {{a.b.c}} bar with a {{x.y}}');
        $this->assertEquals('a.b.c:x.y', implode(':', $tokens));
    }
}
