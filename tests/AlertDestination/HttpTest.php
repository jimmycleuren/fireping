<?php
declare(strict_types=1);

namespace App\Tests\AlertDestination;

use App\AlertDestination\Http;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use App\Exception\ClearException;
use App\Exception\TriggerException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testUrlMissingOnTrigger(): void
    {
        $this->expectException(TriggerException::class);
        (new Http(new Client([])))->trigger($this->createAlert());
    }

    public function createAlert(): Alert
    {
        $device = new Device();
        $device->setName('device');
        $slaveGroup = new SlaveGroup();
        $slaveGroup->setName('group');
        $alertRule = new AlertRule();
        $alertRule->setName('rule');
        $alert = new Alert();
        $alert->setDevice($device);
        $alert->setSlaveGroup($slaveGroup);
        $alert->setAlertRule($alertRule);

        return $alert;
    }

    public function testUrlMissingOnClear(): void
    {
        $this->expectException(ClearException::class);
        (new Http(new Client([])))->clear($this->createAlert());
    }

    public function testExceptionOnTriggerRepacksIt(): void
    {
        $client = $this->createClient(new MockHandler([
            new Response(400),
        ]));

        $http = new Http($client);
        $http->setParameters(['url' => 'url']);

        $this->expectException(TriggerException::class);
        $http->trigger($this->createAlert());
    }

    private function createClient(MockHandler $handler): Client
    {
        return new Client([
            'http_errors' => true,
            'handler' => HandlerStack::create($handler)
        ]);
    }

    public function testExceptionOnClearRepacksIt(): void
    {
        $client = $this->createClient(new MockHandler([
            new Response(400),
        ]));

        $http = new Http($client);
        $http->setParameters(['url' => 'url']);

        $this->expectException(ClearException::class);
        $http->clear($this->createAlert());
    }

    public function testTrigger()
    {
        self::expectNotToPerformAssertions();

        $client = $this->createClient(new MockHandler([
            new Response(200),
        ]));

        $http = new Http($client);
        $http->setParameters(['url' => 'url']);

        $alert = $this->createAlert();

        $http->trigger($alert);
    }

    public function testClear()
    {
        self::expectNotToPerformAssertions();

        $client = $this->createClient(new MockHandler([
            new Response(200),
        ]));

        $http = new Http($client);
        $http->setParameters(['url' => 'url']);

        $alert = $this->createAlert();

        $http->clear($alert);
    }
}
