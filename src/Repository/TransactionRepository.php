<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Transaction $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }


    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findTransactionsByFilter(
        User $user,
        Request $request,
        CourseRepository $courseRepository
    ): array {
        $types = [
            'payment' => 1,
            'deposit' => 2,
        ];

        $type = $request->query->get('type');
        $courseCode = $request->query->get('course_code');
        $skipExpired = $request->query->get('skip_expired');

        $query = $this->createQueryBuilder('t')
            ->andWhere('t.userBilling = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('t.createdAt', 'DESC');

        if ($type) {
            $numberType = $types[$type];
            $query->andWhere('t.type = :type')
                ->setParameter('type', $numberType);
        }
        if ($courseCode) {
            $course = $courseRepository->findOneBy(['code' => $courseCode]);
            $value = $course ? $course->getId() : null;
            $query->andWhere('t.course = :courseId')
                ->setParameter('courseId', $value);
        }
        if ($skipExpired) {
            $query->andWhere('t.expiresAt is null or t.expiresAt >= :today')
                ->setParameter('today', new DateTime());
        }

        return $query->getQuery()->getResult();
    }
    /**
     * @return Transaction[]
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findRentalEndingCourses(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.userBilling = :user_id')
            ->andWhere('t.type = 1')
            ->andWhere('t.expiresAt >= :today AND DATE_DIFF(t.expiresAt, :today) <= 1')
            ->setParameter('today', new \DateTimeImmutable())
            ->setParameter('user_id', $user->getId())
            ->getQuery()
            ->getResult();
    }

    public function getPayStatisticPerMonth()
    {
        $dql = "
            SELECT c.title, 
                   (CASE WHEN c.type = 1 THEN 'Аренда' ELSE 'Покупка' END) as course_type, 
                   COUNT(t.id) as transaction_count, 
                   SUM(t.amount) as total_amount
            FROM App\\Entity\\Transaction t JOIN App\\Entity\\Course c WITH t.course = c.id
            WHERE t.type = 1 AND t.createdAt BETWEEN DATE_SUB(CURRENT_DATE(), 1, 'MONTH') AND CURRENT_DATE()
            GROUP BY c.title, c.type
        ";

        return $this->_em->createQuery($dql)->getResult();

    }

//    /**
//     * @return Transaction[] Returns an array of Transaction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Transaction
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
