<?php

namespace App\Controller;

use App\Entity\User;
use App\Model\UserDTO;
use JMS\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1")
 */
class APIAuthController extends AbstractController
{
    /**
     * @Route("/auth", name="api_auth",  methods={"POST"})
     */
    public function auth(): void
    {
        // get jwt token
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     */
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordEncoder,
        JWTTokenManagerInterface $JWTManager
    ): Response {
        // Десериализация
        try{
            $userDTO = $serializer->deserialize($request->getContent(), UserDTO::class, 'json');
        }
        catch (\Exception $e) {
            dd($e->getMessage());
        }

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
        $entityManager = $this->getDoctrine()->getManager();
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
            $user = User::fromDto($userDTO);
            $user->setPassword($passwordEncoder->hashPassword(
                $user,
                $user->getPassword()
            ));
            $user->setBalance(0);
            $entityManager->persist($user);
            $entityManager->flush();

            $data = [
                // JWT token
                'token' => $JWTManager->create($user),
            ];
            // Устанавливаем статус ответа
            $response->setStatusCode(Response::HTTP_CREATED);
        }
        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);
        return $response;
    }
}
