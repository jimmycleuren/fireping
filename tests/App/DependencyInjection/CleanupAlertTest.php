<?php

namespace Tests\App\DependencyInjection;

use App\DependencyInjection\CleanupAlert;
use App\DependencyInjection\Queue;
use App\DependencyInjection\Worker;
use App\DependencyInjection\WorkerManager;
use App\Entity\Alert;
use App\Repository\AlertRepository;
use Doctrine\ORM\EntityManager;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class CleanupAlertTest extends WebTestCase
{
    private $logger;
    private $alertRepository;
    private $entityManager;
    private $cleanupAlertService;

    public function setUp()
    {
        parent::setUp();

        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        $this->alertRepository = $em->getRepository(Alert::class);

        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->cleanupAlertService = new CleanupAlert(
            $this->logger->reveal(),
            $this->alertRepository,
            $em);
    }

    public function testExecute()
    {
        $countAlertsBefore = count($this->alertRepository->findAll());
        $this->cleanupAlertService->cleanup();
        $countAlertsAfter = count($this->alertRepository->findAll());

        $this->assertEquals(3, $countAlertsBefore - $countAlertsAfter);
    }
}