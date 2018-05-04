<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:58
 */

namespace App\ShellCommand;


use App\OutputFormatter\MtrOutputFormatter;

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

    public function __construct($data)
    {
        parent::__construct($data);
        $this->setOutputFormatter(new MtrOutputFormatter());
    }
}