<?php

namespace App\Tests\App\DependencyInjection;

use App\DependencyInjection\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function testInvalidGetColor()
    {
        $helper = new Helper();

        $this->assertEquals("000000", $helper->getColor(0, 0));
        $this->assertEquals("000000", $helper->getColor(1, 1));
    }

    public function testGetColor()
    {
        $helper = new Helper();

        $this->assertEquals("80f31f", $helper->getColor(0, 1));
        $this->assertEquals("80f31f", $helper->getColor(0, 2));
        $this->assertEquals("800ce0", $helper->getColor(1, 2));
    }
}
