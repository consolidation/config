<?php
namespace Consolidation\Config\Util;

/**
 * Fetch a configuration value from a configuration group. If the
 * desired configuration value is not found in the most specific
 * group named, keep stepping up to the next parent group until a
 * value is located.
 *
 * Given the following constructor inputs:
 *   - $prefix  = "command."
 *   - $group   = "foo.bar.baz"
 *   - $postfix = ".options."
 * Then the `get` method will then consider, in order:
 *   - command.foo.bar.baz.options
 *   - command.foo.bar.options
 *   - command.foo.options
 * If any of these contain an option for "$key", then return its value.
 */
class ApplyConfig
{
    protected $config;

    public function __constructor($config, $group, $prefix = '', $postfix = '.')
    {
        if (!empty($group) && empty($postfix)) {
            $postfix = '.';
        }

        $this->config = new ConfigMerge($config, $group, $prefix = '', $postfix);
    }

    /**
     * Given an object that contains configuration methods, inject any
     * configuration found in the configuration file.
     *
     * The proper use for this method is to call setter methods of the
     * provided object. Using configuration to call methods that do work
     * is an abuse of this mechanism.
     *
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
