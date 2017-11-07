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
     * @var Data
     */
    protected $defaults;

    /**
     * Create a new configuration object, and initialize it with
     * the provided nested array containing configuration data.
     */
    public function __construct(array $data = null)
    {
        $this->config = new Data($data);
        $this->defaults = new Data();
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
        return $this->replace($data);
    }

    /**
     * {@inheritdoc}
     */
    public function replace($data)
    {
        $this->config = new Data($data);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function combine($data)
    {
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
        return $this->defaults->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault($key, $defaultFallback = null)
    {
        return $this->hasDefault($key) ? $this->defaults->get($key) : $defaultFallback;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefault($key, $value)
    {
        $this->defaults->set($key, $value);
        return $this;
    }
}
