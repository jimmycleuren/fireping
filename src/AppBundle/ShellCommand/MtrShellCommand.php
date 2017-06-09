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
    );
    protected $REQUIRED_ARGUMENTS = array('-c');
    protected $EXTRA_ARGUMENTS = array('-n', '--json');
    protected $MAX_TARGETS = 1;

    public function __construct($name, $args)
    {
        parent::__construct($name, $args);
        $this->setOutputFormatter(new MtrOutputFormatter());
    }
}