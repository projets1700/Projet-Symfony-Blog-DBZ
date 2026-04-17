<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-blog', description: 'Cree un admin et des categories Dragon Ball Z')]
class SeedBlogCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@blog.local']);
        if (!$admin) {
            $admin = new User();
            $admin->setEmail('admin@blog.local');
            $admin->setFirstName('Admin');
            $admin->setLastName('Principal');
            $admin->setIsActive(true);
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
            $this->entityManager->persist($admin);
        }
        $admin->setRoles(['ROLE_ADMIN']);

        $demoUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'fan@dbz.local']);
        if (!$demoUser) {
            $demoUser = new User();
            $demoUser->setEmail('fan@dbz.local');
            $demoUser->setFirstName('Son');
            $demoUser->setLastName('Fan');
            $demoUser->setIsActive(true);
            $demoUser->setPassword($this->passwordHasher->hashPassword($demoUser, 'Fan123!'));
            $this->entityManager->persist($demoUser);
        }
        $demoUser->setRoles(['ROLE_USER']);

        foreach ($this->entityManager->getRepository(User::class)->findAll() as $user) {
            if ($user->getEmail() !== 'admin@blog.local') {
                $user->setRoles(['ROLE_USER']);
                $user->setIsActive(true);
            }
        }

        $categories = [];
        $categoryData = [
            'Saiyans' => 'Les guerriers Saiyans, leur heritage et leurs combats legendairement puissants.',
            'Transformations' => 'Toutes les evolutions marquantes de Dragon Ball Z, du Kaioken au Super Saiyan.',
            'Villains' => 'Les antagonistes mythiques qui ont pousse les heros dans leurs derniers retranchements.',
            'Saga Cell' => 'Analyses et moments forts autour de la saga Cell et des Cell Games.',
            'Saga Buu' => 'Les transformations de Majin Buu, Vegeto et le combat final pour sauver la Terre.',
            'Tournois' => 'Les affrontements, qualifications et duels les plus memorables des tournois.'
        ];

        foreach ($categoryData as $categoryName => $description) {
            $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $categoryName]);
            if (!$category) {
                $category = new Category();
                $category->setName($categoryName);
                $this->entityManager->persist($category);
            }

            $category->setDescription($description);
            $categories[$categoryName] = $category;
        }

        // Supprime les anciennes categories hors theme demandees par l'utilisateur.
        foreach ($this->entityManager->getRepository(Category::class)->findAll() as $existingCategory) {
            $name = $existingCategory->getName();
            if (!is_string($name)) {
                continue;
            }

            if (preg_match('/^(symfony|php|actualit)/i', $name) === 1) {
                $this->entityManager->remove($existingCategory);
            }
        }

        $postData = [
            [
                'title' => 'Pourquoi la transformation en Super Saiyan de Goku a change tout Dragon Ball Z',
                'category' => 'Transformations',
                'picture' => '/images/goku-user-hero.png',
                'content' => "Son Goku est le coeur de Dragon Ball Z. Depuis son arrivee face a Vegeta jusqu'au combat final contre Majin Buu, il incarne le courage, la progression constante et la volonte de proteger les autres.\n\nCe qui rend Goku unique, ce n'est pas seulement sa puissance. C'est sa capacite a repousser ses limites a chaque crise, a apprendre de ses adversaires et a transformer chaque combat en occasion de devenir meilleur. Son affrontement contre Freezer sur Namek reste l'exemple parfait de cette evolution, car il y passe du statut de guerrier exceptionnel a celui de legende.\n\nA travers le Super Saiyan, l'entrainement avec Kaio, le sacrifice contre Cell ou encore le Super Saiyan 3, Dragon Ball Z construit Son Goku comme une figure heroique incontournable. Plus qu'un simple personnage principal, il est le symbole meme du depassement de soi dans toute la serie.",
            ],
            [
                'title' => 'Vegeta contre Cell : le prince des Saiyans face a son orgueil',
                'category' => 'Saga Cell',
                'picture' => '/images/goku-user-hero.png',
                'content' => "Dans la saga Cell, Vegeta atteint un cap decisif avec sa forme Super Vegeta.\n\nSon combat contre Cell montre a la fois sa puissance incroyable et sa plus grande faiblesse : son orgueil. En laissant Cell absorber C-18 pour obtenir sa forme parfaite, Vegeta provoque une catastrophe qu'il ne parvient plus a controler.\n\nCet instant est essentiel car il montre que la force brute ne suffit pas dans Dragon Ball Z. L'intelligence tactique et la maitrise emotionnelle comptent tout autant.",
            ],
            [
                'title' => 'Majin Buu : pourquoi cet ennemi est l\'un des plus imprevisibles de la serie',
                'category' => 'Saga Buu',
                'picture' => '/images/goku-user-hero.png',
                'content' => "Majin Buu n'est pas seulement puissant : il est totalement imprevisible.\n\nSa capacite a changer de forme, de personnalite et de niveau de menace le rend unique parmi les villains de Dragon Ball Z. Innocent, cruel, enfantin ou destructeur, il oblige les heros a revoir constamment leur strategie.\n\nLa saga Buu pousse aussi la serie vers une echelle de puissance enorme, avec l'apparition de Vegeto, du Super Saiyan 3 et du Genkidama final.",
            ],
            [
                'title' => 'Les Saiyans les plus marquants de DBZ et ce qu\'ils apportent a l\'histoire',
                'category' => 'Saiyans',
                'picture' => '/images/goku-user-hero.png',
                'content' => "Goku, Vegeta, Gohan, Trunks et Goten incarnent chacun une facette differente du peuple Saiyan.\n\nGoku represente le depassement de soi. Vegeta symbolise la fierte et la rivalite. Gohan montre que le potentiel cache peut surpasser l'entrainement. Trunks apporte une dimension tragique avec son futur detruit.\n\nLeur opposition de caracteres est l'une des grandes forces narratives de Dragon Ball Z.",
            ],
        ];

        foreach ($postData as $index => $data) {
            $post = $this->entityManager->getRepository(Post::class)->findOneBy(['title' => $data['title']]);
            if (!$post) {
                $post = new Post();
                $post->setTitle($data['title']);
                $this->entityManager->persist($post);
            }

            $post->setContent($data['content']);
            $post->setPicture($data['picture']);
            $post->setAuthor($admin);
            $post->setCategory($categories[$data['category']]);
            $post->setPublishedAt(new \DateTimeImmutable(sprintf('-%d days', 7 - $index)));

            $commentContent = match ($index) {
                0 => 'Ce moment contre Freezer reste pour moi le vrai sommet emotionnel de toute la serie.',
                1 => 'Vegeta et son orgueil, c est vraiment ce qui rend ce personnage incroyable a analyser.',
                2 => 'La saga Buu est parfois chaotique, mais c est justement ce qui la rend memorable.',
                default => 'Les Saiyans portent vraiment toute la richesse dramatique de Dragon Ball Z.'
            };

            $existingComment = $this->entityManager->getRepository(Comment::class)->findOneBy([
                'post' => $post,
                'author' => $demoUser,
            ]);

            if (!$existingComment) {
                $existingComment = new Comment();
                $existingComment->setPost($post);
                $existingComment->setAuthor($demoUser);
                $this->entityManager->persist($existingComment);
            }

            $existingComment->setContent($commentContent);
            $existingComment->setStatus('approved');
        }

        $this->entityManager->flush();
        $output->writeln('Donnees Dragon Ball Z creees. Admin: admin@blog.local / Admin123! User: fan@dbz.local / Fan123!');

        return Command::SUCCESS;
    }
}
