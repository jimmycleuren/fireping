<?php

namespace App\DependencyInjection;

use App\Entity\Alert;
use App\Repository\AlertRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CleanupAlert.
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
        EntityManagerInterface $entityManager
    )
    {
        $this->alertRepository = $alertRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function cleanup(): void
    {
        $alerts = $this->alertRepository->findAll();

        $removeAlertsSlaveGroup = array_filter($alerts, function (Alert $alert) {
            return !in_array($alert->getSlaveGroup(), $alert->getDevice()->getActiveSlaveGroups()->toArray());
        });

        $this->removeAlerts($removeAlertsSlaveGroup);

        $removeAlertsAlertRule = array_filter(array_diff($alerts, $removeAlertsSlaveGroup), function (Alert $alert) {
            return !in_array($alert->getAlertRule(), $alert->getDevice()->getActiveAlertRules()->toArray());
        });

        $this->removeAlerts($removeAlertsAlertRule);

        $removeAlertsProbe = array_filter(array_diff($alerts, $removeAlertsSlaveGroup, $removeAlertsAlertRule), function (Alert $alert) {
            return !in_array($alert->getAlertRule()->getProbe(), $alert->getDevice()->getActiveProbes()->toArray());
        });

        $this->removeAlerts($removeAlertsProbe);

        $this->entityManager->flush();
    }

    private function removeAlerts(array $removeAlerts): void
    {
        foreach ($removeAlerts as $alert) {
            $this->logger->info('Alert '.$alert->getId().' from device '.$alert->getDevice()->getName().' will be removed');

            $this->entityManager->remove($alert);
        }
    }
}
