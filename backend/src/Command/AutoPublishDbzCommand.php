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
    description: 'Publie automatiquement cinq articles DBZ generes localement'
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
        $targetCount = 5;
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@blog.local']);
        if (!$admin instanceof User) {
            $output->writeln('<error>Admin introuvable. Lance d abord app:seed-blog.</error>');

            return Command::FAILURE;
        }

        $usedSubjectKeys = [];
        foreach ($this->entityManager->getRepository(Post::class)->findAll() as $existingPost) {
            $subjectKey = $this->extractSubjectKeyFromTitle($existingPost->getTitle());
            if (null !== $subjectKey) {
                $usedSubjectKeys[] = $subjectKey;
            }
        }
        $usedSubjectKeys = array_values(array_filter(array_unique($usedSubjectKeys)));

        $maxAttempts = 30;
        $publishedCount = 0;
        for ($attempt = 1; $attempt <= $maxAttempts; ++$attempt) {
            if ($publishedCount >= $targetCount) {
                break;
            }

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
            $usedSubjectKeys[] = $generated['subjectKey'];
            ++$publishedCount;
            $output->writeln(sprintf('<info>Article publie (%d/%d): %s</info>', $publishedCount, $targetCount, $post->getTitle()));
        }

        if (0 === $publishedCount) {
            $output->writeln('<error>Aucun article unique n a pu etre genere.</error>');

            return Command::FAILURE;
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('<info>%d article(s) genere(s) automatiquement.</info>', $publishedCount));

        return Command::SUCCESS;
    }

    private function extractSubjectKeyFromTitle(?string $title): ?string
    {
        if (!is_string($title)) {
            return null;
        }

        if (preg_match('/^[^:]+:\s(.+?)\s-\s.+(?:\s\([A-Z0-9]{4}\))?$/', $title, $matches) !== 1) {
            return null;
        }

        $subject = strtolower(trim($matches[1]));
        $subject = preg_replace('/[^a-z0-9]+/', '-', $subject) ?? '';
        $subject = trim($subject, '-');

        return '' === $subject ? null : $subject;
    }
}
