<?php
namespace Consolidation\Config\Util;

/**
 * Given an object that contains configuration methods, inject any
 * configuration found in the configuration file.
 *
 * The proper use for this method is to call setter methods of the
 * provided object. Using configuration to call methods that do work
 * is an abuse of this mechanism.
 */
class ApplyConfig
{
    protected $config;

    public function __construct($config, $group, $prefix = '', $postfix = '.')
    {
        if (!empty($group) && empty($postfix)) {
            $postfix = '.';
        }

        $this->config = new ConfigMerge($config, $group, $prefix, $postfix);
    }

    /**
     * TODO: We could use reflection to test to see if the return type
     * of the provided object is a reference to the object itself. All
     * setter methods should do this. This test is insufficient to guarentee
     * that the method is valid, but it would catch almost every misuse.
     */
    public function apply($object, $configurationKey)
    {
        $settings = $this->config->get($configurationKey);
        foreach ($settings as $setterMethod => $args) {
            // TODO: Should it be possible to make $args a nested array
            // to make this code call the setter method multiple times?
            $fn = [$object, $setterMethod];
            if (is_callable($fn)) {
                call_user_func_array($fn, (array)$args);
            }
        }
    }
}
