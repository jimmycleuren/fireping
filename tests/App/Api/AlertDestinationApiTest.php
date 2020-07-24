<?php

namespace Tests\App\Api;

use App\Tests\App\Api\AbstractApiTest;

class AlertDestinationApiTest extends AbstractApiTest
{
    public function testCollection(): void
    {
        $this->client->request('GET', '/api/alert_destinations.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');
        self::assertJson($this->client->getResponse()->getContent());
    }
}
