<?php
namespace App\Command;

use App\Storage\RrdStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateRrdCommand extends Command
{
    private $em = null;
    private $storage = null;

    public function __construct(EntityManagerInterface $em, RrdStorage $storage)
    {
        $this->em = $em;
        $this->storage = $storage;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:rrd:validate')
            ->setDescription('Validate the existing rrd data and fix where needed');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $devices = $this->em->getRepository("App:Device")->findAll();

        $progress = new ProgressBar($output);
        $progress->start(count($devices));

        foreach ($devices as $device) {
            $probes = $device->getActiveProbes();
            $slavegroups = $device->getActiveSlaveGroups();
            foreach($probes as $probe) {
                foreach ($slavegroups as $slavegroup) {
                    $this->storage->validate($device, $probe, $slavegroup);
                }
            }

            $progress->advance();
        }

        $progress->finish();
        $output->writeln("");
    }
}