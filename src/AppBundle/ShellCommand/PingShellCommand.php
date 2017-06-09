<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:57
 */

namespace AppBundle\ShellCommand;


use AppBundle\OutputFormatter\PingOutputFormatter;

class PingShellCommand extends ShellCommand
{
    protected $command = 'fping';
    protected $MAPPED_ARGUMENTS = array(
        'samples' => '-C',
        'interval' => '-p',
    );
    protected $EXTRA_ARGUMENTS = array('-q');
    protected $REQUIRED_ARGUMENTS = array('-C', '-p');

    public function __construct($name, $args)
    {
        parent::__construct($name, $args);
        $this->setOutputFormatter(new PingOutputFormatter());
    }
}