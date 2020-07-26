<?php

declare(strict_types=1);

namespace App\Tests\App\Api;


use App\Repository\AlertDestinationRepository;

class EmailDestinationTest extends AbstractApiTest
{
    public function testCollection(): void
    {
        $this->client->request('GET', '/api/email_destinations.json');

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testGetResource(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'mail']);

        $this->client->request('GET', "/api/email_destinations/{$destination->getId()}.json");

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulCreate(): void
    {
        $this->client->request('POST', '/api/email_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'mail_create_test',
            'recipient' => 'user@fireping.example'
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testSuccessfulPatch(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'mail']);

        self::assertSame('test@test.com', $destination->getRecipient());

        $emailAddress = 'admin@fireping.example';
        $this->client->request('PATCH', "/api/email_destinations/{$destination->getId()}.json", [], [], ['CONTENT_TYPE' => 'application/merge-patch+json'], json_encode([
            'recipient' => $emailAddress
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame($emailAddress, $data['recipient']);
    }

    public function testSuccessfulPut(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $destination = $repository->findOneBy(['name' => 'mail']);

        self::assertSame('test@test.com', $destination->getRecipient());

        $emailAddress = 'admin@fireping.example';
        $this->client->request('PUT', "/api/email_destinations/{$destination->getId()}.json", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'recipient' => $emailAddress
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame($emailAddress, $data['recipient']);
    }

    public function testSuccessfulRemove(): void
    {
        $repository = new AlertDestinationRepository($this->client->getContainer()->get('doctrine'));
        $id = $repository->findOneBy(['name' => 'unused-mail'])->getId();
        $this->client->request('DELETE', "/api/email_destinations/$id.json");

        self::assertResponseStatusCodeSame(204);
    }

    public function testRequiredParametersCannotBeBlank(): void
    {
        $this->client->request('POST', '/api/email_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));

        self::assertResponseStatusCodeSame(400);
        self::assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('violations', $data);
        self::assertSame([
            [
                'propertyPath' => 'recipient',
                'message' => 'This value should not be blank.'
            ],
            [
                'propertyPath' => 'name',
                'message' => 'This value should not be blank.'
            ]
        ], $data['violations']);
    }

    public function testMustHaveValidRecipient(): void
    {
        $this->client->request('POST', '/api/email_destinations.json', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'new-mail',
            'recipient' => 'not-an-email-address',
        ]));

        self::assertResponseStatusCodeSame(400);
        self::assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('violations', $data);
        self::assertSame([
            [
                'propertyPath' => 'recipient',
                'message' => 'This value is not a valid email address.'
            ]
        ], $data['violations']);
    }
}