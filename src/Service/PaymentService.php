<?php


namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    // Оплата курса
    public function paymentCourses(User $user, Course $course): Transaction
    {
        $this->em->getConnection()->beginTransaction();
        try {
            if ($user->getBalance() < $course->getPrice()) {
                throw new \Exception('На вашем счету недостаточно средств', 406);
            }
            $transaction = new Transaction();
            $transaction->setType(1);
            $transaction->setUserBilling($user);
            $transaction->setCreatedAt(new DateTime());
            $transaction->setAmount($course->getPrice());

            if ('rent' === $course->getTypeFormatString()) {
                $time = (new DateTime())->add(new DateInterval('P1W')); // 1 неделя
                $transaction->setExpiresAt($time);
            }

            $transaction->setCourse($course);

            $user->setBalance($user->getBalance() - $course->getPrice());

            $this->em->persist($transaction);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $transaction;
    }
}