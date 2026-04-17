<?php

namespace App\Command;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:ensure-analyse-category',
    description: 'Cree la categorie Analyse si elle n existe pas encore'
)]
class EnsureAnalyseCategoryCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->entityManager->getRepository(Category::class);
        $existing = $repository->findOneBy(['name' => Category::ANALYSIS_CATEGORY_NAME]);
        if ($existing instanceof Category) {
            $output->writeln('<info>La categorie Analyse existe deja.</info>');

            return Command::SUCCESS;
        }

        $category = new Category();
        $category->setName(Category::ANALYSIS_CATEGORY_NAME);
        $category->setDescription(
            'Analyses approfondies de l oeuvre Dragon Ball Z. Les articles de cette categorie sont regroupes sur la page Analyse de l oeuvre.'
        );

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $output->writeln('<info>Categorie Analyse creee avec succes.</info>');

        return Command::SUCCESS;
    }
}
