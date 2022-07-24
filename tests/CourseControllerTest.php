<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\AppFixtures;
use App\Model\CourseDTO;
use App\Model\PayDTO;
use App\Service\PaymentService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CourseControllerTest extends AbstractTest
{
    /** @var SerializerInterface */
    private $serializer;

    private string $apiPath = '/api/v1/courses';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [
            new AppFixtures(
                self::getContainer()->get(UserPasswordHasherInterface::class),
                self::getContainer()->get(PaymentService::class),
                self::getContainer()->get(RefreshTokenGeneratorInterface::class),
                self::getContainer()->get(RefreshTokenManagerInterface::class)
            ),
            new CourseFixtures()
        ];
    }

    private function getToken($user)
    {
        $client = self::getClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    public function testGetAllCourses()
    {

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertCount(8, $response);
    }

    public function testGetCourseByCodeAuthorizedUser()
    {
        $user = [
            'username' => 'artem@user.com',
            'password' => '123654'
        ];
        $token = $this->getToken($user);

        $existingCourseCode = 'PPBI';

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/' . $existingCourseCode,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        /** @var CourseDTO $courseDto */
        $courseDto = $this->serializer->deserialize($client->getResponse()->getContent(), CourseDTO::class, 'json');

        self::assertEquals('PPBI', $courseDto->getCode());
        self::assertEquals('rent', $courseDto->getType());
        self::assertEquals(2000, $courseDto->getPrice());

        $notExistingCourseCode = '123';
        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/' . $notExistingCourseCode,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseCode(Response::HTTP_NOT_FOUND, $client->getResponse());
    }

    public function testPayCourseAuthorizedUser()
    {
        $user = [
            'username' => 'artem@user.com',
            'password' => '123654'
        ];
        $token = $this->getToken($user);


        $courseCode = 'PPBI';

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/' . $courseCode . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        /** @var PayDTO $paymentDto */
        $paymentDto = $this->serializer->deserialize($client->getResponse()->getContent(), PayDTO::class, 'json');

        self::assertEquals(true, $paymentDto->getSuccess());
    }

//    public function testPayCourseUnauthorizedUser()
//    {
//        $courseCode = 'PPBI';
//
//        $client = self::getClient();
//

//        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
//    }
//
    public function testAddCourseAdminUser()
    {
        $user = [
            'username' => 'anna@admin.com',
            'password' => '123654'
        ];
        $token = $this->getToken($user);

        $courseCreationRequest = new CourseDto('TEST', 'rent', 1000);
        $courseCreationRequest->setTitle('TEST');
        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($courseCreationRequest, 'json'),
        );

        $this->assertResponseCode(Response::HTTP_CREATED);

        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals(true, $respose['success']);
    }


    public function testAddExistingCourseAdminUser()
    {
        $user = [
            'username' => 'anna@admin.com',
            'password' => '123654'
        ];
        $token = $this->getToken($user);


        $courseCreationRequest = new CourseDto('PPBI', 'rent', 1000);
        $courseCreationRequest->setTitle('TEST');

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($courseCreationRequest, 'json'),
        );


        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals('Курс с данным кодом уже существует в системе', $respose['message']);
    }

    public function testAddCourseUser()
    {
        $user = [
            'username' => 'artem@user.com',
            'password' => '123654'
        ];
        $token = $this->getToken($user);


        $courseCreationRequest = new CourseDto('PPBI', 'rent', 1000);
        $courseCreationRequest->setTitle('TEST');

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($courseCreationRequest, 'json'),
        );

        $this->assertResponseCode(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testEditCourseUser()
    {
        $user = [
            'username' => 'artem@user.com',
            'password' => '123654'
        ];
        $token = $this->getToken($user);


        $courseCreationRequest = new CourseDto('PPBI12','rent',1000);
        $courseCreationRequest->setTitle('TEST');

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/PPBI/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($courseCreationRequest, 'json'),
        );

        $this->assertResponseCode(Response::HTTP_FORBIDDEN );
    }

    public function testEditCourseAdminUser()
    {
        $user = [
            'username' => 'anna@admin.com',
            'password' => '123654'
        ];
        $token = $this->getToken($user);

        $courseCreationRequest = new CourseDto('PPBI12','rent',1000);
        $courseCreationRequest->setTitle('TEST');

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/PPBI/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($courseCreationRequest, 'json'),
        );

        $this->assertResponseOk();

        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals(true, $respose['success']);
    }

    public function testEditCourseNewExistingCodeAdminUser()
    {
        $user = [
            'username' => 'anna@admin.com',
            'password' => '123654'
        ];
        $token = $this->getToken($user);

        $courseCreationRequest = new CourseDto('PPBI2','rent',1000);
        $courseCreationRequest->setTitle('TEST');

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/PPBI/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($courseCreationRequest, 'json'),
        );

        $this->assertResponseCode(Response::HTTP_METHOD_NOT_ALLOWED);

        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals('Курс с данным кодом уже существует в системе', $respose['message']);
    }

    public function testEditNotExistingCourseCodeAdminUser()
    {
        $user = [
            'username' => 'anna@admin.com',
            'password' => '123654'
        ];
        $token = $this->getToken($user);

        $courseCreationRequest = new CourseDto('PPBI2','rent',1000);
        $courseCreationRequest->setTitle('TEST');

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/PPBfgfgI/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($courseCreationRequest, 'json'),
        );

        $this->assertResponseCode(Response::HTTP_NOT_FOUND);

        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals('Данный курс в системе не найден', $respose['message']);
    }
}