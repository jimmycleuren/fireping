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

    public function testGetResource(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'slack']);

        $this->client->request('GET', "/api/slack_destinations/{$destination->getId()}.json");

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulCreate(): void
    {
        $this->client->request('POST', '/api/slack_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'slack_create_test',
            'url' => 'https://slack.example',
            'channel' => 'channel'
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulPatch(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'slack']);

        self::assertSame('general', $destination->getChannel());

        $this->client->request('PATCH', "/api/slack_destinations/{$destination->getId()}.json", [], [], ['CONTENT_TYPE' => 'application/merge-patch+json'], json_encode([
            'channel' => 'BetterChannel'
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulPut(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'slack']);

        self::assertSame('general', $destination->getChannel());

        $this->client->request('PUT', "/api/slack_destinations/{$destination->getId()}.json", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'channel' => 'BetterChannel',
            'url' => 'https://slack.example'
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulRemove(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $id = $repository->findOneBy(['name' => 'unused-slack'])->getId();
        $this->client->request('DELETE', "/api/slack_destinations/$id.json");

        self::assertResponseStatusCodeSame(204);
    }

    public function testRequiredParametersCannotBeBlank(): void
    {
        $this->client->request('POST', '/api/slack_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));

        self::assertResponseStatusCodeSame(400);
        self::assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('violations', $data);
        self::assertSame([
            [
                'propertyPath' => 'url',
                'message' => 'This value should not be blank.'
            ],
            [
                'propertyPath' => 'channel',
                'message' => 'This value should not be blank.'
            ],
            [
                'propertyPath' => 'name',
                'message' => 'This value should not be blank.'
            ]
        ], $data['violations']);
    }

    public function testMustHaveValidUrl(): void
    {
        $this->client->request('POST', '/api/slack_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'new-slack',
            'url' => 'invalid url',
            'channel' => 'channel',
        ]));

        self::assertResponseStatusCodeSame(400);
        self::assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('violations', $data);
        self::assertSame([
            [
                'propertyPath' => 'url',
                'message' => 'This value is not a valid URL.'
            ]
        ], $data['violations']);
    }
}