<?php
namespace AppBundle\Command;

use AppBundle\AlertDestination\AlertDestinationFactory;
use AppBundle\Entity\Alert;
use AppBundle\Entity\AlertRule;
use AppBundle\Entity\Device;
use AppBundle\Entity\SlaveGroup;
use AppBundle\Storage\RrdStorage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
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
            ->addArgument('destination-id', InputArgument::REQUIRED, "The alert destination id to test")
            ->setDescription('Test an alertdestination');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('destination-id');

        $destination = $this->em->getRepository("AppBundle:AlertDestination")->findOneById($id);
        if (!$destination) {
            $this->logger->warning("Alertdestination #$id not found");
            return;
        }

        $rule = new AlertRule();
        $rule->setName("Test rule");
        $device = new Device();
        $device->setName("Test device");
        $slavegroup = new SlaveGroup();
        $slavegroup->setName("Test group");
        $alert = new Alert();
        $alert->setAlertRule($rule);
        $alert->setDevice($device);
        $alert->setSlaveGroup($slavegroup);

        $instance = $this->alertDestinationFactory->create($destination);
        $instance->trigger($alert);
        $instance->clear($alert);
    }
}