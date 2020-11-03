<?php

declare(strict_types=1);

namespace App\Tests\App\Api;


use App\Repository\AlertDestinationRepository;

class WebhookDestinationTest extends AbstractApiTest
{
    public function testCollection(): void
    {
        $this->client->request('GET', '/api/webhook_destinations.json');

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testGetResource(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'webhook']);

        $this->client->request('GET', "/api/webhook_destinations/{$destination->getId()}.json");

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulCreate(): void
    {
        $this->client->request('POST', '/api/webhook_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'webhook_create_test',
            'url' => 'https://slack.example',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulPatch(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'webhook']);

        self::assertSame('https://example.tld', $destination->getUrl());

        $newUrl = 'https://another.tld';
        $this->client->request('PATCH', "/api/webhook_destinations/{$destination->getId()}.json", [], [], ['CONTENT_TYPE' => 'application/merge-patch+json'], json_encode([
            'url' => $newUrl
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame($newUrl, $data['url']);
    }

    public function testSuccessfulPut(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'webhook']);

        self::assertSame('https://example.tld', $destination->getUrl());

        $newUrl = 'https://another.tld';
        $this->client->request('PUT', "/api/webhook_destinations/{$destination->getId()}.json", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'url' => $newUrl
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame($newUrl, $data['url']);
    }

    public function testSuccessfulRemove(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $id = $repository->findOneBy(['name' => 'unused-webhook'])->getId();
        $this->client->request('DELETE', "/api/webhook_destinations/$id.json");

        self::assertResponseStatusCodeSame(204);
    }

    public function testRequiredParametersCannotBeBlank(): void
    {
        $this->client->request('POST', '/api/webhook_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));

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
                'propertyPath' => 'name',
                'message' => 'This value should not be blank.'
            ]
        ], $data['violations']);
    }

    public function testMustHaveValidUrl(): void
    {
        $this->client->request('POST', '/api/webhook_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'new-webhook',
            'url' => 'invalid url',
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