<?php
namespace AppBundle\Command;

use AppBundle\Probe\DeviceDefinition;
use AppBundle\Probe\Message;
use AppBundle\Probe\MtrResponseFormatter;
use AppBundle\Probe\PingResponseFormatter;
use AppBundle\Probe\PingShellCommand;
use AppBundle\Probe\MtrShellCommand;
use AppBundle\Probe\WorkerResponse;
use AppBundle\ShellCommand\ShellCommandFactory;
use React\EventLoop\Factory;
use React\Stream\ReadableResourceStream;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;

class ProbeWorkerCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('app:probe:worker')
            ->setDescription('Start the probe worker.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $loop = Factory::create();

        $read = new ReadableResourceStream(STDIN, $loop);

        $read->on('data', function ($data) {
            $this->process($data);
        });

        $loop->run();
    }

    protected function process($data)
    {
        $timestamp = time();

        if (!trim($data)) {
            return;
        }

        $data = json_decode($data, true);
        if (!$data) {
            return;
        }

        if (!isset($data['type'])) {
            return;
        }

        $factory = new ShellCommandFactory();
        $command = null;
        try {
            $command = $factory->create($data['type'], $data);
        } catch (\Exception $e) {
            // TODO: Do something with this exception.
        }

        $shellOutput = $command->execute();

        $this->sendResponse(array(
            'status' => 200,
            'message' => 'OK',
            'body' => array(
                $data['id'] => array(
                    'type' => $data['type'],
                    'timestamp' => $timestamp,
                    'targets' => $shellOutput,
                ),
            ),
        ));
    }

    protected function sendResponse($data)
    {
        $json = json_encode($data);
        $this->output->writeln($json);
    }
}