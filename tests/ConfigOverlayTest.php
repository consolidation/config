<?php
namespace Consolidation\Config\Util;

use Consolidation\Config\Config;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;

class ConfigOverlayTest extends \PHPUnit_Framework_TestCase
{
    protected $overlay;

    protected function createOverlay()
    {
        $aliasContext = new Config();
        $aliasContext->import([
            'hidden-by-a' => 'alias hidden-by-a',
            'hidden-by-process' => 'alias hidden-by-process',
            'options' =>[
                'a-a' => 'alias-a',
            ],
            'command' => [
                'foo' => [
                    'bar' => [
                        'command' => [
                            'options' => [
                                'a-b' => 'alias-b',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $configFileContext = new Config();
        $configFileContext->import([
            'hidden-by-cf' => 'config-file hidden-by-cf',
            'hidden-by-a' => 'config-file hidden-by-a',
            'hidden-by-process' => 'config-file hidden-by-process',
            'options' =>[
                'cf-a' => 'config-file-a',
            ],
            'command' => [
                'foo' => [
                    'bar' => [
                        'command' => [
                            'options' => [
                                'cf-b' => 'config-file-b',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $overlay = new ConfigOverlay();
        $overlay->set('hidden-by-process', 'process-h');
        $overlay->addContext('cf', $configFileContext);
        $overlay->addContext('a', $aliasContext);
        $overlay->setDefault('df-a', 'default');
        $overlay->setDefault('hidden-by-a', 'default hidden-by-a');
        $overlay->setDefault('hidden-by-cf', 'default hidden-by-cf');
        $overlay->setDefault('hidden-by-process', 'default hidden-by-process');

        return $overlay;
    }

    public function testGetPriority()
    {
        $overlay = $this->createOverlay();

        $this->assertEquals('process-h', $overlay->get('hidden-by-process'));
        $this->assertEquals('config-file hidden-by-cf', $overlay->get('hidden-by-cf'));
        $this->assertEquals('alias hidden-by-a', $overlay->get('hidden-by-a'));
    }

    public function testDefault()
    {
        $overlay = $this->createOverlay();

        $this->assertEquals('alias-a', $overlay->get('options.a-a'));
        $this->assertEquals('alias-a', $overlay->get('options.a-a', 'ignored'));
        $this->assertEquals('default', $overlay->getDefault('df-a', 'ignored'));
        $this->assertEquals('nsv', $overlay->getDefault('a-a', 'nsv'));

        $overlay->setDefault('df-a', 'new value');
        $this->assertEquals('new value', $overlay->getDefault('df-a', 'ignored'));
    }

    public function testExport()
    {
        $overlay = $this->createOverlay();

        // Set two different values in two contexts on the same key.
        $overlay->getContext('cf')->set('duplicate', 'cf-value');
        $overlay->getContext('a')->set('duplicate', 'a-value');

        $getDuplicateWithStringDefault = $overlay->get('duplicate', 'default');
        $this->assertEquals('a-value', $getDuplicateWithStringDefault);
        $getDuplicateWithArrayDefault = $overlay->get('duplicate', []);
        $this->assertEquals('a-value,cf-value', implode(',', array_values($getDuplicateWithArrayDefault)));

        // Ensure that the value from only the higher-priority context
        // shows up in the export.
        $data = $overlay->export();
        $this->assertEquals('a-value', $data['duplicate']);

        // Also ensure that we have some data from each context in the export.
        $this->assertEquals('config-file-a', $data['options']['cf-a']);
        $this->assertEquals('alias-a', $data['options']['a-a']);
    }

    public function testExportAll()
    {
        $overlay = $this->createOverlay();

        // Set two different values in two contexts on the same key.
        $overlay->getContext('cf')->set('duplicate', 'cf-value');
        $overlay->getContext('a')->set('duplicate', 'a-value');

        $export = $overlay->exportAll();

        $this->assertEquals('cf-value', $export['cf']['duplicate']);
        $this->assertEquals('a-value', $export['a']['duplicate']);
    }

    /**
     * @expectedException Exception
     */
    public function testImport()
    {
        $overlay = $this->createOverlay();

        $data = $overlay->import(['a' => 'value']);
    }

    public function testMaintainPriority()
    {
        $overlay = $this->createOverlay();

        // Get and re-add the 'cf' context. Its priority should not change.
        $configFileContext = $overlay->getContext('cf');
        $overlay->addContext('cf', $configFileContext);

        // These asserts are the same as in testGetPriority
        $this->assertEquals('process-h', $overlay->get('hidden-by-process'));
        $this->assertEquals('config-file hidden-by-cf', $overlay->get('hidden-by-cf'));
        $this->assertEquals('alias hidden-by-a', $overlay->get('hidden-by-a'));
    }

    public function testChangePriority()
    {
        $overlay = $this->createOverlay();

        // Increase the priority of the 'cf' context. Now, it should have a higher
        // priority than the 'alias' context, but should still have a lower
        // priority than the 'process' context.
        $overlay->increasePriority('cf');

        // These asserts are the same as in testGetPriority
        $this->assertEquals('process-h', $overlay->get('hidden-by-process'));
        $this->assertEquals('config-file hidden-by-cf', $overlay->get('hidden-by-cf'));

        // This one has changed: the config-file value is now found instead
        // of the alias value.
        $this->assertEquals('config-file hidden-by-a', $overlay->get('hidden-by-a'));
    }

    public function testPlaceholder()
    {
        $overlay = $this->createOverlay();
        $overlay->addPlaceholder('lower');

        $higherContext = new Config();
        $higherContext->import(['priority-test' => 'higher']);

        $lowerContext = new Config();
        $lowerContext->import(['priority-test' => 'lower']);

        // Usually 'lower' would have the highest priority, since it is
        // added last. However, our earlier call to 'addPlaceholder' reserves
        // a spot for it, so the 'higher' context will end up with a higher
        // priority.
        $overlay->addContext('higher', $higherContext);
        $overlay->addContext('lower', $lowerContext);
        $this->assertEquals('higher', $overlay->get('priority-test', 'neither'));

        // Test to see that we can change the value of the 'higher' context,
        // and the change will be reflected in the overlay.
        $higherContext->set('priority-test', 'changed');
        $this->assertEquals('changed', $overlay->get('priority-test', 'neither'));

        // Test to see that the 'process' context still has the highest priority.
        $overlay->set('priority-test', 'process');
        $higherContext->set('priority-test', 'ignored');
        $this->assertEquals('process', $overlay->get('priority-test', 'neither'));
    }

    public function testDoesNotHave()
    {
        $overlay = $this->createOverlay();
        $context = $overlay->getContext('no-such-context');
        $data = $context->export();
        $this->assertEquals('[]', json_encode($data));

        $this->assertTrue(!$overlay->has('no-such-key'));
        $this->assertTrue(!$overlay->hasDefault('no-such-default'));

        $this->assertEquals('no-such-value', $overlay->get('no-such-key', 'no-such-value'));

    }
}
