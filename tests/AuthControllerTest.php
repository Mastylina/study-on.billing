<?php


namespace App\Tests;

use App\DataFixtures\AppFixtures;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends AbstractTest
{
    /**
     * @var string
     */
    private $startingPath = '/api/v1';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function getFixtures(): array
    {
        return [AppFixtures::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    // Тест успешного входа в систему
    public function testAuthSuccessful(): void
    {
        // Авторизируемся существующим пользователем
        $user = [
            'username' => 'anna@admin.com',
            'password' => '123654',
        ];

        // Формируем запрос
        $client = self::getClient();
        $client->jsonRequest(
            'POST',
            $this->startingPath . '/auth',
            $user
        );

        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверка заголовка ответа, что он действительно в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа (token)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
    }

    // Тест неуспешного входа в систему
    public function testAuthUnsuccessful(): void
    {
        // Авторизируемся существующим пользователем, но не с верным паролем
        $user = [
            'username' => 'anna@admin.com',
            'password' => 'user911',
        ];

        // Формируем запрос
        $client = self::getClient();
        $client->jsonRequest(
            'POST',
            $this->startingPath . '/auth',
            $user
        );

        // Проверка статуса ответа, 401
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());

        // Проверка заголовка ответа, что он действительно в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа (Сообщение об оишбке)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['message']);
    }

    // Тест успешной регистрации
    public function testRegisterSuccessful(): void
    {
        // Передадим данные о новом пользователе
        $user = [
            'username' => 'testUser2000@mail.ru',
            'password' => '123654',
        ];

        // Формируем запрос
        $client = self::getClient();
        $client->jsonRequest(
            'POST',
            $this->startingPath . '/register',
            $user
        );

        // Проверка статуса ответа, 201
        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        // Проверка заголовка ответа, что он действительно в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа (token)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
    }

    // Тест для неуспешной регистрации
    public function testExistUserRegister(): void
    {
        //_____________Проверка на уже существующего пользователя_____________
        // Данные пользователя
        $user = [
            'username' => 'anna@admin.com',
            'password' => '123654',
        ];

        // Формируем запрос
        $client = self::getClient();
        $client->jsonRequest(
            'POST',
            $this->startingPath . '/register',
            $user
        );

        // Проверка статуса ответа, 403
        $this->assertResponseCode(Response::HTTP_FORBIDDEN, $client->getResponse());

        // Проверка заголовка ответа, что он действительно в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа (Сообщение об ошибке)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals('Пользователь с данным email уже существует', $json['message']);

        //_____________Проверка валидации полей_____________
        // Данные пользователя, где пароль состоит менее чем из 6-и символов
        $user = [
            'email' => 'testAnna@yandex.ru',
            'password' => 'test',
        ];

        // Формируем запрос
        $client = self::getClient();
        $client->jsonRequest(
            'POST',
            $this->startingPath . '/register',
            $user
        );

        // Проверка статуса ответа, 400
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());

        // Проверка заголовка ответа, что он действительно в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа (Сообщение об ошибке)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['message']);
    }
}