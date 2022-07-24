<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Service\PaymentService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends AbstractTest
{
    private $serializer;
    private string $apiPath = '/api/v1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }


    protected function getFixtures(): array
    {
        return [
            new AppFixtures(
                self::getContainer()->get(UserPasswordHasherInterface::class),
                self::getContainer()->get(PaymentService::class),
                self::getContainer()->get(RefreshTokenGeneratorInterface::class),
                self::getContainer()->get(RefreshTokenManagerInterface::class)
            )];
    }

    public function testAuthWithExistingUser(): void
    {
        $user = [
            'username' => 'artem@user.com',
            'password' => '123654'
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseOk();

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
        self::assertNotEmpty($json['refresh_token']);
    }

    public function testAuthWithNotExistingUser(): void
    {
        $user = [
            'username' => 'aaartem@user.com',
            'password' => '123654'
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath.'/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],

            $this->serializer->serialize($user, 'json')
        );


        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);

        self::assertNotEmpty($json['code']);
        self::assertNotEmpty($json['message']);

        self::assertEquals('401', $json['code']);
        self::assertEquals('Invalid credentials.', $json['message']);
    }

    public function testRegistrationSuccessful(): void
    {
        $user = [
            'username' => 'test@test.local',
            'password' => 'Qwerty123'
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath.'/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_CREATED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
        self::assertNotEmpty($json['refresh_token']);
    }

    public function testRegistrationValidationErrors(): void
    {
        $user = [
            'username' => 'teststudy-on.local',
            'password' => 'Qwerty123'
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath.'/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);

        self::assertNotEmpty($json['message']);
        self::assertNotEmpty($json['message'][0]['message']);

        // Проверка валидации длины пароля
        $user = [
            'username' => 'test@study-on.local',
            'password' => '123'
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath.'/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);

        self::assertNotEmpty($json['message']);
        self::assertNotEmpty($json['message'][0]['message']);


        // Проверка валидации полей на пустоту
        $user = [
            'username' => '',
            'password' => ''
        ];

        $client->request(
            'POST',
            $this->apiPath.'/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['message']);
        self::assertNotEmpty($json['message'][0]['message']);
        self::assertNotEmpty($json['message'][1]['message']);


        // Проверка валидации поля email на корректность
        $user = [
            'username' => 'email',
            'password' => 'Qwerty123'
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath.'/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['message']);
        self::assertNotEmpty($json['message'][0]['message']);


        // Проверка валидации при существующем пользователе
        $user = [
            'username' => 'artem@user.com',
            'password' => '123654'
        ];

        $client->request(
            'POST',
            $this->apiPath.'/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );


        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);

        self::assertNotEmpty($json['code']);
        self::assertNotEmpty($json['message']);

    }
}