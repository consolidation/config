<?php
namespace Consolidation\Config;

use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use Consolidation\Config\Util\Interpolator;
use PHPUnit\Framework\TestCase;

class InterpolatorTest extends TestCase
{
    public function testFindTokens()
    {
        $interpolator = new Interpolator();

        $tokens = $interpolator->findTokens('This is a {{a.b.c}} bar with a {{x.y}}');
        $this->assertEquals('a.b.c:x.y', implode(':', $tokens));
    }

    public function testReplacements()
    {
        $interpolator = new Interpolator();

        $data = ['a.b.c' => 'foo'];
        $replacements = $interpolator->replacements($data, 'This is a {{a.b.c}} bar with a {{x.y}}', 'default');

        $this->assertEquals('{"{{a.b.c}}":"foo","{{x.y}}":"default"}', json_encode($replacements, true));
    }

    public function inerpolatorTestValues()
    {
        return [
            [
                'the result is bar',
                'the result is {{foo}}',
                ['foo' => 'bar'],
                'default',
            ],
            [
                'the result is default',
                'the result is {{foo}}',
                ['a' => 'b'],
                'default',
            ],
            [
                'the result is {{foo}}',
                'the result is {{foo}}',
                ['a' => 'b'],
                false,
            ],
        ];
    }

    /**
     * @dataProvider inerpolatorTestValues
     */
    public function testInterpolator($expected, $message, $data, $default)
    {
        $interpolator = new Interpolator();

        $actual = $interpolator->interpolate($data, $message, $default);
        $this->assertEquals($expected, $actual);
    }
}
