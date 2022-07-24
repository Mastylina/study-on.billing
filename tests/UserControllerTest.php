<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use App\Model\UserDTO;
use App\Service\PaymentService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends AbstractTest
{
    /**
     * @var string
     */
    private $startingPath = '/api/v1';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function getFixtures(): array
    {
        return [new AppFixtures(
            self::getContainer()->get(UserPasswordHasherInterface::class),
            self::getContainer()->get(PaymentService::class),
            self::getContainer()->get(RefreshTokenGeneratorInterface::class),
            self::getContainer()->get(RefreshTokenManagerInterface::class)
        )];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    public function auth($user): array
    {
        // Создание запроса
        $client = self::getClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            [ 'CONTENT_TYPE' => 'application/json' ],
            $this->serializer->serialize($user, 'json')
        );


        // Проверка содержимого ответа (В ответе должен быть представлен token)
        return json_decode($client->getResponse()->getContent(), true);
    }

    // Тест получении данных о пользователе
    public function testCurrent(): void
    {
        // Авторизация обычным пользователем
        $user = [
            'username' => 'artem@user.com',
            'password' => '123654',
        ];
        $data = $this->auth($user);
        // Получаем токен
        $token = $data['token'];
        self::assertNotEmpty($token);

        //_____________Проверка успешной операции получения данных_____________
        $client = self::getClient();
        // Формирование верного запроса
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ];

        $client->request(
            'GET',
            $this->startingPath.'/users/current',
            [],
            [],
            $contentHeaders
        );
        // Проверка статуса ответа, 200
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверка заголовка ответа, что он действительно в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        /** @var UserDTO $responseUserDTO */
        $responseUserDTO = $this->serializer->deserialize($client->getResponse()->getContent(), UserDTO::class, 'json');

        // Получим данные о пользователе из бд и сравним
        $em = self::getEntityManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => $responseUserDTO->username]);
        // Сравнение данных
        self::assertEquals($responseUserDTO->username, $user->getEmail());
        self::assertEquals($responseUserDTO->roles[0], $user->getRoles()[0]);
        self::assertEquals($responseUserDTO->balance, $user->getBalance());

        //_____________Проверка неуспешной операции (jwt токен неверный)_____________
        $token = 'шишль мышль';
        // Передаем неверный токен
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ];

        $client->request(
            'GET',
            $this->startingPath.'/users/current',
            [],
            [],
            $contentHeaders
        );
        // Проверка статуса ответа, 404
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json);
        self::assertEquals('404', $json['code']);
        self::assertEquals('Данного пользователя не существует', $json['message']);
    }
}