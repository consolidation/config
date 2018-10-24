<?php
namespace Consolidation\Config\Util;

use Consolidation\Config\Config;
use Consolidation\Config\ConfigInterface;

/**
 * Provides configuration objects with an 'interpolate' method
 * that may be used to inject config values into tokens embedded
 * in strings..
 */
trait ConfigInterpolatorTrait
{
    /**
     * @inheritdoc
     */
    public function interpolate($message, $default = '')
    {
        $replacements = $this->replacements($message, $default);
        return strtr($message, $replacements);
    }

    /**
     * @inheritdoc
     */
    public function mustInterpolate($message)
    {
        $result = $this->interpolate($message, false);
        $tokens = $this->findTokens($result);
        if (!empty($tokens)) {
            throw new \Exception('The following required keys were not found in configuration: ' . implode(',', $tokens));
        }
        return $result;
    }

    /**
     * findTokens finds all of the tokens in the provided message
     *
     * @param string $message String with tokens
     * @return string[] map of token to key, e.g. {{key}} => key
     */
    protected function findTokens($message)
    {
        if (!preg_match_all('#{{([a-zA-Z0-9._-]+)}}#', $message, $matches, PREG_SET_ORDER)) {
            return [];
        }
        $tokens = [];
        foreach ($matches as $matchSet) {
            list($sourceText, $key) = $matchSet;
            $tokens[$sourceText] = $key;
        }
        return $tokens;
    }

    /**
     * Replacements looks up all of the replacements in the configuration
     * object, given the token keys from the provided message. Keys that
     * do not exist in the configuration are replaced with the default value.
     */
    protected function replacements($message, $default = '')
    {
        $tokens = $this->findTokens($message);

        $replacements = [];
        foreach ($tokens as $sourceText => $key) {
            $replacementText = $this->get($key, $default);
            if ($replacementText !== false) {
                    $replacements[$sourceText] = $replacementText;
            }
        }
        return $replacements;
    }
}
