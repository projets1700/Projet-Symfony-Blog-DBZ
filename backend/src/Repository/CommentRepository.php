<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function createAdminModerationQueryBuilder()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')->addSelect('p')
            ->leftJoin('p.category', 'category')->addSelect('category')
            ->leftJoin('category.parent', 'parentCategory')->addSelect('parentCategory')
            ->leftJoin('c.author', 'a')->addSelect('a')
            ->addSelect('CASE WHEN parentCategory.id IS NULL THEN category.name ELSE parentCategory.name END AS HIDDEN rootCategoryName')
            ->addSelect('CASE WHEN parentCategory.id IS NULL THEN \'\' ELSE category.name END AS HIDDEN subCategoryName')
            ->orderBy('rootCategoryName', 'ASC')
            ->addOrderBy('subCategoryName', 'ASC')
            ->addOrderBy('p.title', 'ASC')
            ->addOrderBy('c.createdAt', 'DESC');
    }
}
