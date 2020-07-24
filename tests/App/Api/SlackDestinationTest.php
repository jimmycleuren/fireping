<?php

declare(strict_types=1);

namespace App\Tests\App\Api;

use App\Repository\AlertDestinationRepository;

class SlackDestinationTest extends AbstractApiTest
{
    public function testCollection(): void
    {
        $this->client->request('GET', '/api/slack_destinations.json');

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulCreate(): void
    {
        $this->client->request('POST', '/api/slack_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name'    => 'slack_create_test',
            'url'     => 'https://slack.example',
            'channel' => 'channel'
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulPatch(): void
    {
        $this->client->request('POST', '/api/slack_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name'    => 'slack_create_test',
            'url'     => 'https://slack.example',
            'channel' => 'channel'
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulRemove(): void
    {
        $alertDestinationRepository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $id = $alertDestinationRepository->findOneBy(['name' => 'unused-slack'])->getId();
        $this->client->request('DELETE', "/api/slack_destinations/$id.json");

        self::assertResponseStatusCodeSame(204);
    }
}