<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $user_admin = new User();
        $user = new User();

        $user_admin->setEmail('anna@admin.com');
        $user_admin->setPassword($this->passwordEncoder->encodePassword($user_admin, '123654'));
        $user_admin->setRoles(['ROLE_SUPER_ADMIN']);
        $user_admin->setBalance(4);

        $user->setEmail('artem@user.com');
        $user->setPassword($this->passwordEncoder->encodePassword($user, '123654'));
        $user->setBalance(4);
        $manager->persist($user_admin);
        $manager->persist($user);

        $manager->flush();
    }
}
