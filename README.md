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

Under development.

## Motivation

Provide a simple Config class that can be injected where needed to provide configuration values in non-command classes, and make configuration settings a no-op for command classes by automatically initializing the Input object from configuration as needed.

## API Usage

### Load Configuration Files
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
[dflydev/dot-access-data](https://github.com/dflydev/dot-access-data) is levereaged to provide this capability.

## Comparison to Existing Solutions

Drush has an existing procedural mechanism for loading configuration values from multiple files, and overlaying the results in priority order.  Command-specific options from configuration files and site aliases may also be applied.

The [Symfony Configuration](http://symfony.com/doc/current/components/config.html) component provides the capability to locate configuration file, load them from either YAML or XML sources, and validate that they match a certain defined schema. 
