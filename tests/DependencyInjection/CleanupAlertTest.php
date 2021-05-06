<?php

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\CleanupAlert;
use App\Entity\Alert;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CleanupAlertTest extends WebTestCase
{
    private $alertRepository;
    private $cleanupAlertService;

    public function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        $this->alertRepository = $em->getRepository(Alert::class);

        $logger = $this->prophesize(LoggerInterface::class);

        $this->cleanupAlertService = new CleanupAlert(
            $logger->reveal(),
            $this->alertRepository,
            $em);
    }

    public function testExecute()
    {
        $countAlertsBefore = count($this->alertRepository->findAll());
        $this->cleanupAlertService->cleanup();
        $countAlertsAfter = count($this->alertRepository->findAll());

        $this->assertEquals(7, $countAlertsBefore - $countAlertsAfter);
    }
}
