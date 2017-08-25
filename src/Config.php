<?php
namespace Consolidation\Config;

use Dflydev\DotAccessData\Data;

class Config implements ConfigInterface
{
    /**
     * @var Data
     */
    protected $config;

    /**
     * @var array
     */
    protected $defaults;

    /**
     * Create a new configuration object, and initialize it with
     * the provided nested array containing configuration data.
     */
    public function __construct(array $data = null)
    {
        $this->config = new Data($data);
        $this->defaults = [];
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return ($this->config->has($key));
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $defaultFallback = null)
    {
        if ($this->has($key)) {
            return $this->config->get($key);
        }
        return $this->getDefault($key, $defaultFallback);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->config->set($key, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($data)
    {
        $this->config = new Data($data);
        if (!empty($data)) {
            $this->config->import($data, true);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        return $this->config->export();
    }

    /**
     * {@inheritdoc}
     */
    public function hasDefault($key)
    {
        // Return immediately if exact value is available
        if (isset($this->defaults[$key])) {
            return true;
        }
        
        // Check to see if $key has a partial match
        foreach ($this->defaults as $defaultKey => $value) {
            if (0 === strpos($defaultKey, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault($key, $defaultFallback = null)
    {
        // Return fallback if no default exists
        if (!$this->hasDefault($key)) {
            return $defaultFallback;
        }

        // Return the exact value if available
        if (isset($this->defaults[$key])) {
            return $this->defaults[$key];
        }

        // Create an array with all available values
        $defaults = [];
        $length = strlen($key);
        foreach ($this->defaults as $defaultKey => $value) {
            if (0 === strpos($defaultKey, $key)) {
                $partialKey = substr($defaultKey, $length);
                if (0 === strpos($partialKey, '.')) {
                    $partialKey = substr($partialKey, 1);
                }
                $defaults[$partialKey] = $value;
            }
        }
        return $defaults;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefault($key, $value)
    {
        $this->defaults[$key] = $value;
        return $this;
    }
}
