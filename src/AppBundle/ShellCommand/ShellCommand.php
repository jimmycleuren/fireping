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
    protected $command;
    protected $targets = array();

    /* TODO: This should probably be more reasonable... :D */
    protected $MAX_TARGETS = 10000;

    const SERIAL_EXECUTION = 1;
    const PARALLEL_EXECUTION = 2;

    protected $EXECUTION_MODE = self::PARALLEL_EXECUTION;

    protected $arguments;

    protected $REQUIRED_ARGUMENTS = array();
    protected $EXTRA_ARGUMENTS = array();
    protected $MAPPED_ARGUMENTS = array();

    /* @var $outputFormatter OutputFormatterInterface */
    protected $outputFormatter;

    public function __construct($data)
    {
        $finder = new ExecutableFinder();
        if (!$finder->find($this->command)) {
            throw new \Exception($this->command . " is not installed on this system.");
        }

        $this->arguments = $this->mapArguments($data['args']);
        $this->targets = $data['targets'];
        $this->outputFormatter = new DefaultOutputFormatter();
    }

    /**
     * @param mixed $outputFormatter
     */
    public function setOutputFormatter(OutputFormatterInterface $outputFormatter)
    {
        $this->outputFormatter = $outputFormatter;
    }

    /** Runs a built command if validations passed.
     * @return array shell output
     * @throws \Exception if validations failed.
     */
    public function execute()
    {
        $errors = $this->valid();
        if (count($errors)) {
            throw new \Exception("ShellCommand validations failed: " . json_encode($errors));
        }

        $output = array();

        foreach ($this->build() as $id => $command) {
            $out = '';
            exec($command, $out);

            $shellOutput = $this->formatOutput($out);

            switch ($this->EXECUTION_MODE) {
                case self::SERIAL_EXECUTION:
                    $output[$id] = $shellOutput;
                    break;
                case self::PARALLEL_EXECUTION:
                    foreach ($shellOutput as $key => $result) {
                        $deviceId = $this->targets[$key]['id'];
                        $output[$deviceId] = $result['result'];
                    }
                    break;
                default:
                    throw new \Exception("Invalid execution mode.");
            }
        }

        return $output;
    }

    public function buildArguments() : string
    {
        $str = '';
        foreach ($this->arguments as $param => $value) {
            if (!isset($value)) {
                $str .= ' ' . $param;
            } else {
                $str .= ' ' . $param . ' ' . $value;
            }
        }
        return $str;
    }

    public function buildTargets($targets) : string
    {
        $ipAddresses = array_map(function($device) {
            return $device['ip'];
        }, $targets);
        return ' ' . implode(' ', $ipAddresses);
    }

    /** Base function to build the command which can be run in a shell.
     * @return array
     */
    public function build()
    {
        $commands = array();
        switch ($this->EXECUTION_MODE) {
            case self::SERIAL_EXECUTION:
                foreach ($this->targets as $device) {
                    $commands[$device['id']] = $this->buildCommand(array($device));
                }
                break;
            case self::PARALLEL_EXECUTION:
                $commands[] = $this->buildCommand($this->targets);
                break;
            default:
                throw new \Exception("Invalid execution mode.");
        }
        return $commands;
    }

    private function buildCommand($targets)
    {
        // build the executable
        $command = $this->command;

        // build the arguments
        $command .= $this->buildArguments();

        // build any additional arguments
        if ($this->EXTRA_ARGUMENTS) {
            $command .= ' ' . implode(' ', $this->EXTRA_ARGUMENTS);
        }

        // build the targets
        $command .= $this->buildTargets($targets);

        // build output
        $command .= ' ' . '2>&1'; // output stderr to stdout.
        return $command;
    }

    /** Base validation for shell commands.
     * @return array should return an array containing all the failed validations.
     */
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
                if ($value === true) {
                    $mapped[$mapped_key] = null;
                } else {
                    $mapped[$mapped_key] = $value;
                }
            }
        }
        return $mapped;
    }

}