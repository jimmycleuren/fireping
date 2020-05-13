<?php

namespace App\Tests\App\DependencyInjection;

use App\DependencyInjection\CleanupAlert;
use App\Entity\Alert;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class CleanupAlertTest extends WebTestCase
{
    use ProphecyTrait;

    private $alertRepository;
    private $cleanupAlertService;

    public function setUp() : void
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