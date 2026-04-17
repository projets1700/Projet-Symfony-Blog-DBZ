<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Cree ou complete les articles de la categorie Analyse (page Analyse de l oeuvre).
 */
class AnalysisArticleProvisioner
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkAnalysisArticleGenerator $workAnalysisArticleGenerator
    ) {
    }

    /**
     * Garantit au moins $targetTotal articles dans la categorie Analyse (cree les manquants).
     *
     * @return int nombre d articles reellement crees lors de cet appel
     */
    public function ensureArticles(Category $analysisCategory, User $author, int $targetTotal = 5): int
    {
        $currentCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Post::class, 'p')
            ->where('p.category = :cat')
            ->setParameter('cat', $analysisCategory)
            ->getQuery()
            ->getSingleScalarResult();

        $need = max(0, $targetTotal - $currentCount);
        if (0 === $need) {
            return 0;
        }

        $usedSubjectKeys = [];

        $created = 0;
        $maxAttempts = 40;
        for ($attempt = 0; $attempt < $maxAttempts && $created < $need; ++$attempt) {
            $generated = $this->workAnalysisArticleGenerator->generateArticle($usedSubjectKeys);
            if (null === $generated) {
                break;
            }

            $duplicate = $this->entityManager->getRepository(Post::class)->findOneBy(['title' => $generated['title']]);
            if ($duplicate instanceof Post) {
                continue;
            }

            $post = new Post();
            $post->setTitle($generated['title']);
            $post->setContent($generated['content']);
            $post->setPicture(null);
            $post->setIsApproved(true);
            $post->setAuthor($author);
            $post->setCategory($analysisCategory);
            $post->setPublishedAt(new \DateTimeImmutable(sprintf('-%d hours', $created + 1)));

            $this->entityManager->persist($post);
            $usedSubjectKeys[] = $generated['subjectKey'];
            ++$created;
        }

        return $created;
    }

    /**
     * Cree exactement $howMany nouveaux articles dans la categorie Analyse (en plus de l existant).
     *
     * @return int nombre d articles reellement crees
     */
    public function addArticles(Category $analysisCategory, User $author, int $howMany): int
    {
        if ($howMany < 1) {
            return 0;
        }

        $usedSubjectKeys = [];
        $created = 0;
        $maxAttempts = 60;
        for ($attempt = 0; $attempt < $maxAttempts && $created < $howMany; ++$attempt) {
            $generated = $this->workAnalysisArticleGenerator->generateArticle($usedSubjectKeys);
            if (null === $generated) {
                break;
            }

            $duplicate = $this->entityManager->getRepository(Post::class)->findOneBy(['title' => $generated['title']]);
            if ($duplicate instanceof Post) {
                continue;
            }

            $post = new Post();
            $post->setTitle($generated['title']);
            $post->setContent($generated['content']);
            $post->setPicture(null);
            $post->setIsApproved(true);
            $post->setAuthor($author);
            $post->setCategory($analysisCategory);
            $post->setPublishedAt(new \DateTimeImmutable(sprintf('-%d minutes', $created + 1)));

            $this->entityManager->persist($post);
            $usedSubjectKeys[] = $generated['subjectKey'];
            ++$created;
        }

        return $created;
    }
}
