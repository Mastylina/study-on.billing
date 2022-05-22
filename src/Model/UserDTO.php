<?php

namespace App\Model;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema(
 *     title="UserDTO",
 *     description="UserDTO"
 * )
 *
 * Class UserDTO
 * @package App\Model
 */
class UserDTO
{
    /**
     * @OA\Property(
     *     format="username",
     *     title="username",
     *     description="username",
     *     example="test@yandex.ru"
     * )
     * @Serializer\Type("string")
     * @Assert\Email(message="Email address {{ value }} is not valid")
     */
    public $username;

    /**
     * @OA\Property(
     *     format="string",
     *     title="Password",
     *     description="Password",
     *     example="test123"
     * )
     * @Serializer\Type("string")
     * @Assert\Length(
     *     min="6",
     *     minMessage="Your password must be at least {{ limit }} characters",
     * )
     * @Assert\NotBlank()
     */
    public $password;
}
