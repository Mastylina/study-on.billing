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
 *
 * Class UserDTO
 * @package App\Model
 */
class UserDTO
{
    /**
     * @OA\Property(
     *     type="string",
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
     *     type="string",
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
    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="string"
     *     ),
     *     title="Roles",
     *     description="Roles"
     * )
     * @Serializer\Type("array")
     */
    public $roles = [];

    /**
     * @OA\Property(
     *     type="float",
     *     title="Balance",
     *     description="Balance"
     * )
     * @Serializer\Type("float")
     */
    public  $balance;
}
