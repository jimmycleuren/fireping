<?php

namespace AppBundle\ShellCommand;
use AppBundle\OutputFormatter\DefaultOutputFormatter;
use AppBundle\OutputFormatter\OutputFormatterInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:38
 */
abstract class ShellCommand implements ShellCommandInterface
{
    protected $name;
    protected $command;
    protected $targets = array();

    protected $MAX_TARGETS = 50;

    protected $arguments;

    protected $REQUIRED_ARGUMENTS = array();
    protected $EXTRA_ARGUMENTS = array();
    protected $MAPPED_ARGUMENTS = array();

    /* @var $outputFormatter OutputFormatterInterface */
    protected $outputFormatter;

    public function __construct($name, $args)
    {
        $finder = new ExecutableFinder();
        if (!$finder->find($this->command)) {
            throw new \Exception($this->command . " is not installed on this system.");
        }

        $this->name = $name;
        $this->arguments = $this->mapArguments($args);
        $this->targets = $args['targets'];
        $this->outputFormatter = new DefaultOutputFormatter();
    }

    /**
     * @param mixed $outputFormatter
     */
    public function setOutputFormatter(OutputFormatterInterface $outputFormatter)
    {
        $this->outputFormatter = $outputFormatter;
    }

    public function execute()
    {
        $valid = $this->valid();
        if (count($valid) == 0) {
            $command = $this->build();
            $out = '';
            exec($command, $out);

            $shellOutput = $this->formatOutput($out);

            $output = array();
            foreach ($shellOutput as $key => $result) {
                $deviceId = $this->targets[$key]['id'];
                $output[$deviceId] = $result['result'];
            }

            return $output;
        } else {
            throw new \Exception("ShellCommand invalid with current arguments: " . json_encode($valid));
        }
    }

    public function build()
    {
        $command = $this->command;
        $command .= ' ' . str_replace('=', ' ',
                http_build_query($this->arguments, null, ' '));

        if ($this->EXTRA_ARGUMENTS) {
            $command .= ' ' . implode(' ', $this->EXTRA_ARGUMENTS);
        }

        $ipAddresses = array_map(function($device) {
            return $device['ip'];
        }, $this->targets);

        $command .= ' ' . implode(' ', $ipAddresses);
        $command .= ' ' . '2>&1'; // output stderr to stdout.
        return $command;
    }

    public function valid()
    {
        $errors = array();

        $keys = array_keys($this->arguments);
        $hasRequired = array_intersect($keys, $this->REQUIRED_ARGUMENTS) == $this->REQUIRED_ARGUMENTS;

        if (!$hasRequired) {
            $errors['MissingArguments'] = 'Missing required arguments.';
        }

        $tooFewArguments = count($this->targets) <= 0;

        if ($tooFewArguments) {
            $errors['TooFewArguments'] = 'Should have at least one target.';
        }

        $tooManyArguments = count($this->targets) > $this->MAX_TARGETS;

        if ($tooManyArguments) {
            $errors['TooManyArguments'] = 'Should have at most ' . $this->MAX_TARGETS . ' targets.';
        }

        return $errors;
    }

    public function formatOutput($input)
    {
        return $this->outputFormatter->format($input);
    }

    public function mapArguments(array $args)
    {
        $mapped = array();
        foreach ($args as $key => $value) {
            if (array_key_exists($key, $this->MAPPED_ARGUMENTS)) {
                $mapped_key = $this->MAPPED_ARGUMENTS[$key];
                $mapped[$mapped_key] = $value;
            }
        }
        return $mapped;
    }

}