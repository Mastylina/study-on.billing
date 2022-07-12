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

    // Успешный вход
    public function testAuthSuccessful(): void
    {

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
        // Авторизируемся существующим пользователем с неверным паролем
        $user = [
            'username' => 'anna@admin.com',
            'password' => 'user1235',
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
        // Регистрация нового пользователя
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
        //Регистрация существующего пользователя
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

        // Проверка заголовка ответа, что он в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа (Сообщение об ошибке)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals('Пользователь с данным email уже существует', $json['message']);

        //Пароль меньше 6 символов
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