<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\User;
use App\Service\AnalysisArticleProvisioner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-analysis-articles',
    description: 'Cree des articles dans la categorie Analyse (Analyse de l oeuvre). Par defaut: 5 nouveaux articles.'
)]
class GenerateAnalysisArticlesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AnalysisArticleProvisioner $analysisArticleProvisioner
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('count', InputArgument::OPTIONAL, 'Nombre d articles a creer', 5)
            ->addOption('ensure', null, InputOption::VALUE_NONE, 'Ne cree que les articles manquants pour atteindre au total 5 (comportement seed)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@blog.local']);
        if (!$admin instanceof User) {
            $output->writeln('<error>Admin introuvable. Lance d abord app:seed-blog.</error>');

            return Command::FAILURE;
        }

        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => Category::ANALYSIS_CATEGORY_NAME]);
        if (!$category instanceof Category) {
            $output->writeln('<error>Categorie Analyse introuvable. Lance: php bin/console app:ensure-analyse-category</error>');

            return Command::FAILURE;
        }

        if ($input->getOption('ensure')) {
            $created = $this->analysisArticleProvisioner->ensureArticles($category, $admin, 5);
        } else {
            $count = max(1, (int) $input->getArgument('count'));
            $created = $this->analysisArticleProvisioner->addArticles($category, $admin, $count);
        }

        $this->entityManager->flush();

        if (0 === $created) {
            if ($input->getOption('ensure')) {
                $output->writeln('<info>La categorie Analyse contient deja au moins 5 articles. Aucun nouvel article cree.</info>');

                return Command::SUCCESS;
            }

            $output->writeln('<error>Aucun article n a pu etre genere (reessayez).</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>%d article(s) Analyse de l oeuvre cree(s).</info>', $created));

        return Command::SUCCESS;
    }
}
