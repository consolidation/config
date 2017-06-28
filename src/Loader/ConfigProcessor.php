<?php

namespace Consolidation\Config\Loader;

use Grasmash\YamlExpander\Expander;

/**
 * The config processor combines multiple configuration
 * files together, and processes them as necessary.
 */
class ConfigProcessor
{
    protected $processedConfig = [];
    protected $unprocessedConfig = [];

    /**
     * Extend the configuration to be processed with the
     * configuration provided by the specified loader.
     *
     * @param ConfigLoaderInterface $loader
     */
    public function extend(ConfigLoaderInterface $loader)
    {
        return $this->addFromSource($loader->export(), $loader->getSourceName());
    }

    /**
     * Extend the configuration to be processed with
     * the provided nested array.
     *
     * @param array $data
     */
    public function add($data)
    {
        $this->unprocessedConfig[] = $data;
        return $this;
    }

    /**
     * Extend the configuration to be processed with
     * the provided nested array. Also record the name
     * of the data source, if applicable.
     *
     * @param array $data
     * @param string $source
     */
    protected function addFromSource($data, $source = '')
    {
        if (empty($source)) {
            return $this->add($data);
        }
        $this->unprocessedConfig[$source] = $data;
        return $this;
    }

    /**
     * Process all of the configuration that has been collected,
     * and return a nested array.
     *
     * @return array
     */
    public function export()
    {
        if (!empty($this->unprocessedConfig)) {
            $this->processedConfig = $this->process(
                $this->processedConfig,
                $this->fetchUnprocessed()
            );
        }
        return $this->processedConfig;
    }

    /**
     * To aid in debugging: return the source of each configuration item.
     * n.b. Must call this function *before* export and save the result
     * if persistence is desired.
     */
    public function sources()
    {
        $sources = [];
        foreach ($this->unprocessedConfig as $sourceName => $config) {
            if (!empty($sourceName)) {
                $configSources = self::arrayFillRecursive($config, $sourceName);
                $sources = self::arrayMergeRecursiveDistinct($sources, $configSources);
            }
        }
        return $sources;
    }

    /**
     * Get the configuration to be processed, and clear out the
     * 'unprocessed' list.
     *
     * @return array
     */
    protected function fetchUnprocessed()
    {
        $toBeProcessed = $this->unprocessedConfig;
        $this->unprocessedConfig = [];
        return $toBeProcessed;
    }

    /**
     * Use a map-reduce to evaluate the items to be processed,
     * and merge them into the processed array.
     *
     * @param array $processed
     * @param array $toBeProcessed
     * @return array
     */
    protected function process(array $processed, array $toBeProcessed)
    {
        $toBeReduced = array_map([$this, 'preprocess'], $toBeProcessed);
        $reduced = array_reduce($toBeReduced, [$this, 'reduceOne'], $processed);
        return $this->evaluate($reduced);
    }

    /**
     * Process a single configuration file from the 'to be processed'
     * list. By default this is a no-op. Override this method to
     * provide any desired configuration preprocessing, e.g. dot-notation
     * expansion of the configuration keys, etc.
     *
     * @param array $config
     * @return array
     */
    protected function preprocess(array $config)
    {
        return $config;
    }

    /**
     * Evaluate one item in the 'to be evaluated' list, and then
     * merge it into the processed configuration (the 'carry').
     *
     * @param array $processed
     * @param array $config
     * @return array
     */
    protected function reduceOne(array $processed, array $config)
    {
        return self::arrayMergeRecursiveDistinct($processed, $config);
    }

    /**
     * Evaluate one configuration item.
     *
     * @param array $processed
     * @param array $config
     * @return array
     */
    protected function evaluate(array $config)
    {
        return Expander::expandArrayProperties(
            $config,
            []
        );
    }

    /**
     * Merges arrays recursively while preserving.
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     *
     * @see http://php.net/manual/en/function.array-merge-recursive.php#92195
     * @see https://github.com/grasmash/bolt/blob/robo-rebase/src/Robo/Common/ArrayManipulator.php#L22
     */
    protected static function arrayMergeRecursiveDistinct(
        array &$array1,
        array &$array2
    ) {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            $merged[$key] = self::mergeRecursiveValue($merged, $key, $value);
        }
        return $merged;
    }

    /**
     * Process the value in an arrayMergeRecursiveDistinct - make a recursive
     * call if needed.
     */
    private static function mergeRecursiveValue(&$merged, $key, $value)
    {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            return self::arrayMergeRecursiveDistinct($merged[$key], $value);
        }
        return $value;
    }

    /**
     * Fills all of the leaf-node values of a nested array with the
     * provided replacement value.
     */
    protected static function arrayFillRecursive(array $data, $fill)
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = $fill;
            if (self::isAssociativeArray($value)) {
                $result[$key] = self::arrayFillRecursive($value, $fill);
            }
        }
        return $result;
    }

    /**
     * Return true if the provided parameter is an array, and at least
     * one key is non-numeric.
     */
    protected static function isAssociativeArray($testArray)
    {
        if (!is_array($testArray)) {
            return false;
        }
        foreach (array_keys($testArray) as $key) {
            if (!is_numeric($key)) {
                return true;
            }
        }
        return false;
    }
}
