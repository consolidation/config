# Consolidation\Config

Manage configuration for a commandline tool.

[![Travis CI](https://travis-ci.org/consolidation/config.svg?branch=master)](https://travis-ci.org/consolidation/config) 
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/consolidation/config/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/consolidation/config/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/consolidation/config/badge.svg?branch=master)](https://coveralls.io/github/consolidation/config?branch=master)
[![License](https://poser.pugx.org/consolidation/config/license)](https://packagist.org/packages/consolidation/config)

This component is designed to provide the components needed to manage configuration options from different sources, including:

- Commandline options
- Configuration files
- Alias files (special configuration files that identify a specific target site)
- Default values (provided by command)

Symfony Console is used to provide the framework for the commandline tool, and the Symfony Configuration component is used to load and merge configuration files.  This project provides the glue that binds the components together in an easy-to-use package.

## Component Status

Factored out of Robo. Candidate for 1.0.0-stable.

## Motivation

Provide a simple Config class that can be injected where needed to provide configuration values in non-command classes, and make configuration settings a no-op for command classes by automatically initializing the Input object from configuration as needed.

## Configuration File Usage

Configuration files are simple hierarchical yaml files.

### Providing Command Options

Command options are defined by creating an entry for each command name under the `command:` section of the configuration file. The options for the command should be defined within an `options:` section. For example, to set a configuration value `red` for the option `--color` in the `example` command:
```
command:
  example:
    options:
      color: red
```
If a command name contains a `:`, then each section of the command name defines another level of heirarchy in the command option configuration. For example, to set a configuration value `George` for the option `--name` of the command `my:foo`:
```
command:
  my:
    foo:
      options:
        name: George
```
Furthermore, every level of the command name heirarchy may contain options. For example, to define a configuration value for the option `--dir` for any command that begins with `my:`:
```
command:
  my:
    options:
      dir: '/base/path'
    foo:
      options:
        name: George
    bar:
      options:
        priority: high
```

### Configuration Value Substitution

It is possible to define values in a configuration file that will be substituted in wherever used. For example:
```
common:
  path: '/shared/path'
command:
  my:
    options:
      dir: '${common.path}'
    foo:
      options:
        name: George
```

[grasmash/yaml-expander](https://github.com/grasmash/yaml-expander) is used to provide this capability.

## API Usage

The easiest way to utilize the capabilities of this project is to use [Robo as a framework](https://robo.li/framework) to create your commandline tools. Using Robo is optional, though, as this project will work with any Symfony Console application.

### Load Configuration Files with Provided Loader

Consolidation/config includes a built-in yaml loader / processor. To use it directly, use a YamlConfigLoader to load each of your configuration files, and a ConfigProcessor to merge them together. Then, export the result from the configuration processor, and import it into a Config object.
```
use Consolidation\Config\Config;
use Consolidation\Config\YamlConfigLoader;
use Consolidation\Config\ConfigProcessor;

$config = new Config();
$loader = new YamlConfigLoader();
$processor = new ConfigProcessor();
$processor->extend($loader->load('defaults.yml'));
$processor->extend($loader->load('myconf.yml'));
$config->import($processor->export());
```

### Set Up Command Option Configuration Injection

The command option configuration feature described above in the section `Providing Command Options` is provided via a configuration injection class. All that you need to do to use this feature as attach this object to your Symfony Console application's event dispatcher:
```
$application = new Symfony\Component\Console\Application($name, $version);
$configInjector = new \Consolidation\Config\Inject\ConfigForCommand($config);
$configInjector->setApplication($application);

$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addSubscriber($configInjector);
$application->setDispatcher($eventDispatcher);
```

### Get Configuration Values
If you have a configuration file that looks like this:
```
a:
  b:
    c: foo
```
Then you can fetch the value of the configuration option `c` via: 
```
$value = $config->get('a.b.c');
```
[dflydev/dot-access-data](https://github.com/dflydev/dot-access-data) is leveraged to provide this capability.

## External Examples

### Load Configuration Files with Symfony/Config

The [Symfony Config](http://symfony.com/doc/current/components/config.html) component provides the capability to locate configuration file, load them from either YAML or XML sources, and validate that they match a certain defined schema. Classes to find configuration files are also available.

If these features are needed, the results from `Symfony\Component\Config\Definition\Processor::processConfiguration()` may be provided directly to the `Consolidation\Config\Config::import()` method.

### Use Configuration to Call Setter Methods

[Robo](https://robo.li) provides a facility for configuration files to [define default values for task setter methods](http://robo.li/getting-started/#configuration-for-task-settings). This is done via the `ConfigForSetters::apply()` method.
```
$taskClass = static::configClassIdentifier($taskClass);
$configurationApplier = new \Consolidation\Config\Inject\ConfigForSetters($this->getConfig(), $taskClass, 'task.');
$configurationApplier->apply($task, 'settings');
```
The `configClassIdentifier` method converts `\`-separated class and namespace names into `.`-separated identifiers; it is provided by ConfigAwareTrait:
```
protected static function configClassIdentifier($classname)
{
    $configIdentifier = strtr($classname, '\\', '.');
    $configIdentifier = preg_replace('#^(.*\.Task\.|\.)#', '', $configIdentifier);

    return $configIdentifier;
}
```
A similar pattern may be used in other applications that may wish to inject values into objects using their setter methods.

## Comparison to Existing Solutions

Drush has an existing procedural mechanism for loading configuration values from multiple files, and overlaying the results in priority order.  Command-specific options from configuration files and site aliases may also be applied.

