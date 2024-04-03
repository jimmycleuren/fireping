<?php

namespace App\Command;

use App\Entity\Device;
use App\Storage\RrdStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateRrdCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly RrdStorage $storage)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:rrd:validate')
            ->setDescription('Validate the existing rrd data and fix where needed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $devices = $this->em->getRepository(Device::class)->findAll();

        $progress = new ProgressBar($output);
        $progress->start(count($devices));

        foreach ($devices as $device) {
            $probes = $device->getActiveProbes();
            $slavegroups = $device->getActiveSlaveGroups();
            foreach ($probes as $probe) {
                foreach ($slavegroups as $slavegroup) {
                    $this->storage->validate($device, $probe, $slavegroup);
                }
            }

            $progress->advance();
        }

        $progress->finish();
        $output->writeln('');

        return 0;
    }
}
