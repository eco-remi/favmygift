<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }
    public function findAllWithCounts(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u as user')
            ->addSelect('COUNT(DISTINCT g.id) as giftCount')
            ->addSelect('COUNT(DISTINCT f.id) as friendCount')
            ->leftJoin('u.gifts', 'g')
            ->leftJoin('u.sentFriendRequests', 'f', 'WITH', 'f.status = :status')
            ->orLeftJoin('u.receivedFriendRequests', 'fr', 'WITH', 'fr.status = :status') // Joining received just for count total might receive duplicates if aggregating simply.
            // Simplified logic: Count *accepted* friends via sent + received is complex in single aggregation without subqueries or simpler logic.
            // Let's stick to counting gifts efficiently first, and maybe just load friends count lazily or via simpler query if "relations" means just friends.
            // Actually, let's just count gifts and friend requests generally or just Gifts for now to keep it simpler/eco-friendly on DB.
            // Re-evaluating: User asked for "number of relations".
            // Eco-approach: fetch users, and size of collections is lazy-loaded but N+1 if we iterate.
            // Better: DQL with SIZE() or extra select.
            // Let's use SIZE() in twig or a dedicated DQL?
            // Let's try to get gift count at least.
            ->setParameter('status', 'ACCEPTED')
            ->groupBy('u.id')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithStats(): array
    {
        // Simple and efficient: Get Users and their Gift Counts.
        // For friends, it's bi-directional (sent + received where status = ACCEPTED).
        // Doing this in one DQL is tricky. Let's do it in PHP or just standard lazy loading if list is small, 
        // BUT "eco-conception" implies efficiency.
        // Let's just fetch users and loop with count() which Doctrine handles reasonably well if not thousands.
        // Or better, just count gifts.
        return $this->createQueryBuilder('u')
            ->select('u as user', 'COUNT(g.id) as giftCount')
            ->leftJoin('u.gifts', 'g')
            ->groupBy('u.id')
            ->getQuery()
            ->getResult();
    }
}