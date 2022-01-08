<?php

namespace Consolidation\Config\Tests\Unit\Inject;

use Consolidation\Config\Config;
use Consolidation\Config\Inject\ConfigForSetters;
use Consolidation\Config\Tests\Helper\ApplyConfigTestTarget;
use Consolidation\Config\Tests\Unit\TestBase;

class ConfigForSettersTest extends TestBase
{
    public function testApplyConfig()
    {
        $data = [
            // Define some configuration settings for the configuration
            // of some task \My\Tasks\Operations\Frobulate.
            'task' => [
                'Operations' => [
                    // task.Operations.settings apply to all tasks in
                    // any *.Tass.Operations namespace.
                    'settings' => [
                        'dir' => '/base/dir',
                    ],
                    'Frobulate' => [
                        // task.Operations.Frobulate.settings applies only
                        // the Frobulate task.
                        'settings' => [
                            'dir' => '/override/dir',
                        ],
                    ],
                ],
            ],
        ];
        $config = new Config($data);

        $applicator = new ConfigForSetters($config, 'Operations.Frobulate', 'task.');

        $testTarget = new ApplyConfigTestTarget();

        $applicator->apply($testTarget, 'settings');

        $this->assertEquals('/override/dir', $testTarget->getDir());
        $this->assertEquals(null, $testTarget->getBad());
    }

    public function testApplyBadConfig()
    {
        $data = [
            // Define some configuration settings for the configuration
            // of some task \My\Tasks\Operations\Frobulate.
            'task' => [
                'Operations' => [
                    // task.Operations.settings apply to all tasks in
                    // any *.Tass.Operations namespace.
                    'settings' => [
                        'dir' => '/base/dir',
                    ],
                    'Frobulate' => [
                        // task.Operations.Frobulate.settings applies only
                        // the Frobulate task.
                        'settings' => [
                            'bad' => 'fire truck',
                        ],
                    ],
                ],
            ],
        ];
        $config = new Config($data);

        $applicator = new ConfigForSetters($config, 'Operations.Frobulate', 'task.');

        $testTarget = new ApplyConfigTestTarget();

        $exceptionMessage = '';
        try
        {
            $applicator->apply($testTarget, 'settings');
        }
        catch (\Exception $e)
        {
            $exceptionMessage = $e->getMessage();
        }
        // We would prefer it if bad methods were never called; unfortunately,
        // declaring the return type of a method cannot be done in a reliable
        // way (via reflection) until php 7, so we allow these methods to be
        // called for now.
        $this->assertEquals('fire truck', $testTarget->getBad());
        $this->assertEquals('Consolidation\\Config\\Tests\\Helper\\ApplyConfigTestTarget::bad did not return \'$this\' when processing task.Operations.Frobulate.settings.', $exceptionMessage);
    }
}
