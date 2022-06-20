<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $user_admin = new User();
        $user = new User();

        $user_admin->setEmail('anna@admin.com');
        $user_admin->setPassword($this->passwordHasher->hashPassword($user_admin, '123654'));
        $user_admin->setRoles(['ROLE_SUPER_ADMIN']);
        $user_admin->setBalance(4);

        $user->setEmail('artem@user.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, '123654'));
        $user->setBalance(4);
        $manager->persist($user_admin);
        $manager->persist($user);

        $manager->flush();
    }
}
