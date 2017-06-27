<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:58
 */

namespace AppBundle\ShellCommand;


use AppBundle\OutputFormatter\MtrOutputFormatter;

class MtrShellCommand extends ShellCommand
{
    protected $command = 'mtr';
    protected $MAPPED_ARGUMENTS = array(
        'samples' => '-c',
        'interval' => '-i',
        'packet_size' => '-s',
        'grace_period' => '-G',
        'first_ttl' => '-f',
        'max_ttl' => '-m',
        'max_unknown' => '-U',
        'timeout' => '-Z',
    );
    protected $REQUIRED_ARGUMENTS = array('-c');
    protected $EXTRA_ARGUMENTS = array('-n', '--json');

    protected $EXECUTION_MODE = ShellCommand::SERIAL_EXECUTION;

    public function __construct($name, $args)
    {
        parent::__construct($name, $args);
        $this->setOutputFormatter(new MtrOutputFormatter());
    }
}