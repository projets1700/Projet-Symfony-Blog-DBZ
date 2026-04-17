<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return Post[]
     */
    public function findLatest(): array
    {
        return $this->createLatestQueryBuilder(true)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Post[]
     */
    public function findLatestLimited(int $limit): array
    {
        return $this->createLatestQueryBuilder(true)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function createLatestQueryBuilder(bool $onlyApproved = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.author', 'a')
            ->addSelect('a')
            ->leftJoin('p.likes', 'l')
            ->addSelect('l')
            ->orderBy('p.publishedAt', 'DESC')
        ;

        if ($onlyApproved) {
            $qb->andWhere('p.isApproved = :approved')
                ->setParameter('approved', true);
        }

        return $qb;
    }

    public function createFilteredQueryBuilder(?string $query, ?int $categoryId, string $sort): QueryBuilder
    {
        $qb = $this->createLatestQueryBuilder(true);

        if (null !== $query && '' !== trim($query)) {
            $qb->andWhere('p.title LIKE :query OR p.content LIKE :query')
                ->setParameter('query', '%'.trim($query).'%');
        }

        if (null !== $categoryId) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ('oldest' === $sort) {
            $qb->orderBy('p.publishedAt', 'ASC');
        } elseif ('title' === $sort) {
            $qb->orderBy('p.title', 'ASC');
        } elseif ('popular' === $sort) {
            $qb->orderBy('COUNT(l.id)', 'DESC')
                ->addOrderBy('p.publishedAt', 'DESC')
                ->groupBy('p.id, c.id, a.id');
        }

        return $qb;
    }

    public function createApprovedByCategoryQueryBuilder(int $categoryId): QueryBuilder
    {
        return $this->createLatestQueryBuilder(true)
            ->andWhere('c.id = :categoryId')
            ->setParameter('categoryId', $categoryId);
    }

    /**
     * @return array<int, array{title: string, likesCount: int, commentsCount: int}>
     */
    public function findTopArticlesByEngagement(int $limit = 5): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.title AS title')
            ->addSelect('COUNT(DISTINCT l.id) AS likesCount')
            ->addSelect('COUNT(DISTINCT c.id) AS commentsCount')
            ->leftJoin('p.likes', 'l')
            ->leftJoin('p.comments', 'c')
            ->groupBy('p.id')
            ->orderBy('likesCount', 'DESC')
            ->addOrderBy('commentsCount', 'DESC')
            ->addOrderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'title' => (string) $row['title'],
            'likesCount' => (int) $row['likesCount'],
            'commentsCount' => (int) $row['commentsCount'],
        ], $rows);
    }
}
