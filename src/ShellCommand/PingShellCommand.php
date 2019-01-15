<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:57
 */

namespace App\ShellCommand;


use App\OutputFormatter\PingOutputFormatter;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class PingShellCommand extends ShellCommand
{
    protected $command = 'fping';
    protected $MAPPED_ARGUMENTS = array(
        'samples' => '-C',
        'packet_size' => '-s',
        'interval' => '-i',
        'wait_time' => '-p',
        'retries' => '-r',
    );
    protected $EXTRA_ARGUMENTS = array('-q');
    protected $REQUIRED_ARGUMENTS = array('-C');

    public function __construct($data, LoggerInterface $logger, ContainerInterface $container)
    {
        parent::__construct($data, $logger, $container);
        $this->setOutputFormatter(new PingOutputFormatter());
    }
}