<?php
namespace Consolidation\Config;

/**
 * Overlay different configuration objects that implement ConfigInterface
 * to make a priority-based, merged configuration object.
 *
 * Note that using a ConfigOverlay hides the defaults stored in each
 * individual configuration context. When using overlays, always call
 * getDefault / setDefault on the ConfigOverlay object itself.
 */
class ConfigOverlay implements ConfigInterface
{
    protected $contexts = [];

    public function __construct()
    {
        $this->contexts['default'] = new Config();
        $this->contexts['process'] = new Config();
    }

    /**
     * Add a named configuration object to the configuration overlay.
     * Configuration objects added LAST have HIGHEST priority, with the
     * exception of the fact that the process context always has the
     * highest priority.
     */
    public function addContext($name, ConfigInterface $config)
    {
        $process = $this->contexts['process'];
        unset($this->contexts['process']);
        unset($this->contexts[$name]);
        $this->contexts[$name] = $config;
        $this->contexts['process'] = $process;
    }

    public function hasContext($name)
    {
        return isset($this->contexts[$name]);
    }

    public function getContext($name)
    {
        if ($this->hasContext($name)) {
            return $this->contexts[$name];
        }
        return new Config();
    }

    /**
     * Determine if a non-default config value exists.
     */
    public function findContext($key)
    {
        foreach (array_reverse($this->contexts) as $name => $config) {
            if ($config->has($key)) {
                return $config;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return $this->findContext($key) != false;
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null)
    {
        $context = $this->findContext($key);
        if ($context) {
            return $context->get($key, $default);
        }
        return $default;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value)
    {
        return $this->contexts['process']->set($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function import($data)
    {
        throw new \Exception('The method "import" is not supported for the ConfigOverlay class.');
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        $export = [];
        foreach ($this->contexts as $name => $config) {
            $export = array_merge_recursive($export, $config->export());
        }
        return $export;
    }

    /**
     * @inheritdoc
     */
    public function hasDefault($key)
    {
        return $this->contexts['default']->has($key);
    }

    /**
     * @inheritdoc
     */
    public function getDefault($key, $default = null)
    {
        return $this->contexts['default']->get($key, $default);
    }

    /**
     * @inheritdoc
     */
    public function setDefault($key, $value)
    {
        return $this->contexts['default']->set($key, $value);
    }
}
