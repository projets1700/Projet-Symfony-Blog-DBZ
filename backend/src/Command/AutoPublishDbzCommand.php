<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use App\Service\AutoIllustrationGenerator;
use App\Service\DbzArticleGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:autopublish-dbz',
    description: 'Publie automatiquement un article DBZ genere localement'
)]
class AutoPublishDbzCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DbzArticleGenerator $dbzArticleGenerator,
        private readonly AutoIllustrationGenerator $autoIllustrationGenerator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@blog.local']);
        if (!$admin instanceof User) {
            $output->writeln('<error>Admin introuvable. Lance d abord app:seed-blog.</error>');

            return Command::FAILURE;
        }

        $usedSubjectKeys = [];
        foreach ($this->entityManager->getRepository(Post::class)->findAll() as $existingPost) {
            $category = $existingPost->getCategory();
            if (!$category instanceof Category) {
                continue;
            }

            $usedSubjectKeys[] = $this->mapCategoryToSubjectKey($category->getName());
        }
        $usedSubjectKeys = array_values(array_filter(array_unique($usedSubjectKeys)));

        $maxAttempts = 8;
        for ($attempt = 1; $attempt <= $maxAttempts; ++$attempt) {
            $generated = $this->dbzArticleGenerator->generateArticle($usedSubjectKeys);
            if (null === $generated) {
                break;
            }

            $alreadyExists = $this->entityManager->getRepository(Post::class)->findOneBy(['title' => $generated['title']]);
            if ($alreadyExists instanceof Post) {
                continue;
            }

            $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $generated['categoryName']]);
            if (!$category instanceof Category) {
                continue;
            }

            $post = new Post();
            $post->setTitle($generated['title']);
            $post->setContent($generated['content']);
            $illustrationPath = $this->autoIllustrationGenerator->generateAndStore($generated['title'], $generated['subject']);
            $post->setPicture($illustrationPath);
            $post->setIsApproved(false);
            $post->setAuthor($admin);
            $post->setCategory($category);
            $post->setPublishedAt(new \DateTimeImmutable());

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $output->writeln(sprintf('<info>Article publie: %s</info>', $post->getTitle()));

            return Command::SUCCESS;
        }

        $output->writeln('<error>Aucun article unique n a pu etre genere.</error>');

        return Command::FAILURE;
    }

    private function mapCategoryToSubjectKey(?string $categoryName): ?string
    {
        return match ($categoryName) {
            'Saiyans' => 'fierte-saiyan',
            'Transformations' => 'transformations',
            'Villains' => 'ennemis-dbz',
            'Saga Cell' => 'saga-cell',
            'Saga Buu' => 'saga-buu',
            'Tournois' => 'tournois',
            default => null,
        };
    }
}
