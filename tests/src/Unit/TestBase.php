<?php

namespace Consolidation\Config\Tests\Unit;

use PHPUnit\Framework\TestCase;

class TestBase extends TestCase
{

    /**
     * @return string
     */
    protected function getFixturesDir()
    {
        return dirname(__DIR__, 2) . '/data';
    }
}
