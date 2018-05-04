<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/03/2018
 * Time: 20:21
 */

namespace Tests\App\ShellCommand;

use App\ShellCommand\MtrShellCommand;
use PHPUnit\Framework\TestCase;

class MtrShellCommandTest extends TestCase
{
    /**
     * Pass all possible arguments
     */
    public function testAllArguments()
    {
        $command = new MtrShellCommand(array(
            'args' => array(
                'samples' => 5,
                'interval' => 1,
                'packet_size' => 1500,
                'grace_period' => 1000,
                'first_ttl' => 250,
                'max_ttl' => 20,
                'max_unknown' => 30,
                'timeout' => 1000
            ),
            'targets' => array('8.8.8.8')
        ));

        $this->assertEmpty($command->valid());
    }

    /**
     * Test the minimal arguments
     */
    public function testMinimalArguments()
    {
        $command = new MtrShellCommand(array(
            'args' => array(
                'samples' => 5
            ),
            'targets' => array('8.8.8.8')
        ));

        $this->assertEmpty($command->valid());
    }

    /**
     * Test without arguments
     */
    public function testNoArguments()
    {
        $command = new MtrShellCommand(array(
            'args' => array(),
            'targets' => array('8.8.8.8')
        ));

        $this->assertNotEmpty($command->valid());
    }

    /**
     * Test without target
     */
    public function testNoTarget()
    {
        $command = new MtrShellCommand(array(
            'args' => array(
                'samples' => 5
            ),
            'targets' => array()
        ));

        $this->assertNotEmpty($command->valid());
    }
}