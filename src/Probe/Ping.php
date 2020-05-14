<?php
declare(strict_types=1);

namespace App\Probe;

use App\OutputFormatter\PingOutputFormatter;
use App\ShellCommand\CommandInterface;
use Psr\Log\LoggerInterface;

class Ping implements CommandInterface
{
    public const MAX_TARGETS = 1e4;

    private $logger;
    private $formatter;

    private $mappedArguments = [
        'samples' => '-C',
        'packet_size' => '-s',
        'interval' => '-i',
        'wait_time' => '-p',
        'retries' => '-r',
    ];
    private $requiredArguments = ['-C'];

    private $arguments = [];
    private $targets = [];

    public function __construct(LoggerInterface $logger, PingOutputFormatter $formatter)
    {
        $this->logger = $logger;
        $this->formatter = $formatter;
    }

    public function setArgs(array $args): void
    {
        if (count($args['targets']) == 0) {
            throw new \RuntimeException("No targets set for ping probe");
        }
        $args['args']['interval'] = $args['args']['wait_time'] / count($args['targets']);
        $args['args']['retries'] = 0;

        $this->arguments = $this->mapArguments($args['args']);
        $this->targets = $args['targets'];
    }

    public function execute(): array
    {
        $errors = $this->validate();

        if (count($errors)) {
            throw new \Exception("ShellCommand validations failed: " . json_encode($errors));
        }

        $output = array();
        $out = '';
        exec($this->makeCommand(), $out);
        $shellOutput = $this->formatter->format($out);

        foreach ($shellOutput as $key => $result) {
            $deviceId = $this->targets[$key]['id'];
            $output[$deviceId] = $result['result'];
        }

        return $output;
    }

    private function validate(): array
    {
        $errors = [];

        $keys = array_keys($this->arguments);
        $hasRequired = array_intersect($keys, $this->requiredArguments) == $this->requiredArguments;

        if (!$hasRequired) {
            $errors['MissingArguments'] = 'Missing required arguments.';
        }

        $tooFewArguments = count($this->targets) <= 0;

        if ($tooFewArguments) {
            $errors['TooFewArguments'] = 'Should have at least one target.';
        }

        $tooManyArguments = count($this->targets) > self::MAX_TARGETS;

        if ($tooManyArguments) {
            $errors['TooManyArguments'] = 'Should have at most ' . self::MAX_TARGETS . ' targets.';
        }

        return $errors;
    }

    private function makeCommand(): string
    {
        return 'fping' . $this->buildArguments() . ' ' . $this->buildTargets() . ' 2>&1';
    }

    private function buildArguments(): string
    {
        $args = '';

        foreach ($this->arguments as $param => $value) {
            $args .= ' ' . $param;
            if (isset($value)) {
                $args .= ' ' . $value;
            }
        }

        return $args . ' -q';
    }

    private function buildTargets(): string
    {
        $ipAddresses = array_map(function ($device) {
            return $device['ip'];
        }, $this->targets);
        return implode(' ', $ipAddresses);
    }

    private function mapArguments(array $args)
    {
        $mapped = [];
        foreach ($args as $key => $value) {
            if (array_key_exists($key, $this->mappedArguments)) {
                $mapped_key = $this->mappedArguments[$key];
                if ($value === true) {
                    $mapped[$mapped_key] = null;
                } else {
                    $mapped[$mapped_key] = $value;
                }
            }
        }
        return $mapped;
    }

    public function getType(): string
    {
        return 'ping';
    }
}