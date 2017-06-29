<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 20/06/2017
 * Time: 9:29
 */

namespace AppBundle\ShellCommand;


use AppBundle\OutputFormatter\TracerouteOutputFormatter;

class TracerouteShellCommand extends ShellCommand
{
    protected $command = 'traceroute';
    protected $MAPPED_ARGUMENTS = array(
        'samples' => '-q',
        'icmp' => '-I',
        'tcp' => '-T',
        'no_fragmentation' => '-F',
        'first_ttl' => '-f',
        'gateway' => '-g',
        'max_ttl' => '-m',
        'simultaneous_queries' => '-N',
        'wait' => '-w',
    );
    protected $REQUIRED_ARGUMENTS = array('-q');
    protected $EXTRA_ARGUMENTS = array('-d');

    protected $EXECUTION_MODE = ShellCommand::SERIAL_EXECUTION;

    public function __construct($data)
    {
        parent::__construct($data);
        $this->setOutputFormatter(new TracerouteOutputFormatter());
    }
}