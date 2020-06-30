<?php

namespace App\Command;

use App\AlertDestination\AlertDestinationFactory;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestAlertDestinationCommand extends Command
{
    private $em = null;
    private $alertDestinationFactory = null;
    private $logger = null;

    public function __construct(EntityManagerInterface $em, AlertDestinationFactory $alertDestinationFactory, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->alertDestinationFactory = $alertDestinationFactory;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:alert:test')
            ->addArgument('destination-id', InputArgument::REQUIRED, 'The alert destination id to test')
            ->setDescription('Test an alertdestination');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = (int) $input->getArgument('destination-id');

        $destination = $this->em->getRepository('App:AlertDestination')->findOneById($id);
        if (!$destination) {
            $this->logger->warning("Alertdestination #$id not found");

            return 1;
        }

        $rule = new AlertRule();
        $rule->setName('Test rule');
        $device = new Device();
        $device->setName('Test device');
        $slavegroup = new SlaveGroup();
        $slavegroup->setName('Test group');
        $alert = new Alert();
        $alert->setAlertRule($rule);
        $alert->setDevice($device);
        $alert->setSlaveGroup($slavegroup);

        $instance = $this->alertDestinationFactory->create($destination);
        $instance->trigger($alert);
        $instance->clear($alert);

        return 0;
    }
}
