<?php

namespace App\DependencyInjection;
use App\Entity\Alert;
use App\Repository\AlertRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;


/**
 * Class CleanupAlert
 * @package App\DependencyInjection
 */
class CleanupAlert
{

    /**
     * @var AlertRepository
     */
    private $alertRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        AlertRepository $alertRepository,
        EntityManagerInterface $entityManager)
    {

        $this->alertRepository = $alertRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function cleanup(): void
    {
        $alerts = $this->alertRepository->findAll();

        $alertsNoSlaveGroups = array_filter($alerts,function(Alert $alert){
             return !in_array($alert->getSlaveGroup(), $alert->getDevice()->getActiveSlaveGroups()->toArray()) ;
        });

        $alertsNoSlaveGroupsNoAlertRules = array_filter($alertsNoSlaveGroups,function(Alert $alert){
            return !in_array($alert->getAlertRule(), $alert->getDevice()->getActiveAlertRules()->toArray());
        });

        foreach ($alertsNoSlaveGroupsNoAlertRules as $alert) {
            $this->logger->info("Alert ".$alert->getId()." from device ".$alert->getDevice()->getName()." will be removed");
            $this->entityManager->remove($alert);
        }

        $this->entityManager->flush();
    }
}