<?php

namespace App\Controller;


use App\Entity\User;
use App\Model\UserDTO;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Service\RefreshToken;
/**
 * @Route("/api/v1")
 */
class APIAuthController extends AbstractController
{
    /**
     * @OA\Post (
     *     path="/api/v1/auth",
     *     tags={"User"},
     *     summary="Автроизация пользователя",
     *     description="Автроизация пользователя",
     *     operationId="auth",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  example="user@yandex.ru"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  example="user123"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Успешная авторизация",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Неудалось авторизоваться",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="401"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Неверные учетные данные"
     *              )
     *          )
     *     )
     *  )
     * @Route("/auth", name="api_auth",  methods={"POST"})
     */
    public function auth(): void
    {
        // get jwt token
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     tags={"User"},
     *     summary="Регистрация нового пользователя",
     *     description="Регистрация доступна только для новых пользователей",
     *     operationId="register",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="user@yandex.ru"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  example="user123"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="201",
     *          description="Регистрация прошла успешно",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="500",
     *          description="Сервер не отвечает"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Ошибка при валидации данных",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string"
     *                  )
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="403",
     *          description="Данный пользователь уже существует",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *     )
     * )
     * @Route("/register", name="register", methods={"POST"})
     */
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordEncoder,
        JWTTokenManagerInterface $JWTManager,
        EntityManagerInterface $entityManager,
        RefreshTokenManagerInterface $refreshTokenManager
    ): Response {
        // Десериализация
        $userDTO = $serializer->deserialize($request->getContent(), UserDTO::class, 'json');
        $data = [];
        $response = new Response();
        // Проверяем ошибки при валидации
        $validErrors = $validator->validate($userDTO);
        if (count($validErrors)) {
            // Параметры
            $data = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $validErrors,
            ];
            // Статус ответа
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent($serializer->serialize($data, 'json'));
            $response->headers->add(['Content-Type' => 'application/json']);
            return $response;
        }
        // Существует ли данный пользователь в системе
        $userRepository = $entityManager->getRepository(User::class);
        if ($userRepository->findOneBy(['email' => $userDTO->username])) {
            $data = [
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Пользователь с данным email уже существует',
            ];
            // Устанавливаем статус ответа
            $response->setStatusCode(Response::HTTP_FORBIDDEN);
        } else {
            // Создаем пользователя
            //dd($userDTO);
            $user = User::fromDTO($userDTO);
            $user->setPassword($passwordEncoder->hashPassword(
                $user,
                $user->getPassword()
            ));
            $user->setBalance(0);
            $entityManager->persist($user);
            $entityManager->flush();

            $refreshToken = $refreshTokenManager->create();
            $refreshToken->setUsername($user->getEmail());
            $refreshToken->setRefreshToken();
            $refreshToken->setValid((new \DateTime())->modify('+1 month'));
            $refreshTokenManager->save($refreshToken);

            $data = [
                // JWT token
                'token' => $JWTManager->create($user),
                'refresh_token'=> $refreshToken->getRefreshToken(),
            ];
            // Устанавливаем статус ответа
            $response->setStatusCode(Response::HTTP_CREATED);
        }
        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);
        return $response;
    }
    /**
     * @OA\Post(
     *     path="/api/v1/token/refresh",
     *     tags={"User"},
     *     summary="Refresh token",
     *     operationId="token.refresh",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Обновление токена успешно завершено",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Не удалось обновить токен",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="integer",
     *                  example="401"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Произошла ошибка при обновлении токена"
     *              )
     *          )
     *     )
     * )
     *
     * @Route("/token/refresh", name="refresh", methods={"POST"})
     * @param Request $request
     * @param RefreshToken $refreshService
     * @return mixed
     */
    public function refresh(Request $request, RefreshToken $refreshService)
    {
        return $refreshService->refresh($request);
    }
}
