<?php

declare(strict_types=1);

namespace App\Tests\App\Api;


use App\Repository\AlertDestinationRepository;

class LogDestinationTest extends AbstractApiTest
{
    public function testCollection(): void
    {
        $this->client->request('GET', '/api/log_destinations.json');

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testGetResource(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'syslog']);

        $this->client->request('GET', "/api/log_destinations/{$destination->getId()}.json");

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulCreate(): void
    {
        $this->client->request('POST', '/api/log_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'monolog',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulPatch(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'syslog']);

        self::assertSame('syslog', $destination->getName());

        $newName = 'monolog';
        $this->client->request('PATCH', "/api/log_destinations/{$destination->getId()}.json", [], [], ['CONTENT_TYPE' => 'application/merge-patch+json'], json_encode([
            'name' => $newName
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame($newName, $data['name']);
    }

    public function testSuccessfulPut(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'syslog']);

        self::assertSame('syslog', $destination->getName());

        $newName = 'monolog';
        $this->client->request('PUT', "/api/log_destinations/{$destination->getId()}.json", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => $newName
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame($newName, $data['name']);
    }

    public function testSuccessfulRemove(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $id = $repository->findOneBy(['name' => 'unused-log'])->getId();
        $this->client->request('DELETE', "/api/log_destinations/$id.json");

        self::assertResponseStatusCodeSame(204);
    }

    public function testRequiredParametersCannotBeBlank(): void
    {
        $this->client->request('POST', '/api/log_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));

        self::assertResponseStatusCodeSame(400);
        self::assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('violations', $data);
        self::assertSame([
            [
                'propertyPath' => 'name',
                'message' => 'This value should not be blank.'
            ]
        ], $data['violations']);
    }
}