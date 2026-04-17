<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Category;
use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\User;
use App\Form\CommentType;
use App\Repository\CategoryRepository;
use App\Repository\PostLikeRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostController extends AbstractController
{
    #[Route('/articles', name: 'app_post_index')]
    public function index(
        PostRepository $postRepository,
        CategoryRepository $categoryRepository
    ): Response
    {
        $categories = $categoryRepository->findRootCategories();

        return $this->render('post/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/articles/categorie/{id}', name: 'app_post_category', requirements: ['id' => '\d+'])]
    public function byCategory(
        Category $category,
        Request $request,
        PostRepository $postRepository,
        PaginatorInterface $paginator
    ): Response {
        $postsPagination = $paginator->paginate(
            $postRepository->createApprovedByCategoryQueryBuilder((int) $category->getId()),
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('post/category.html.twig', [
            'category' => $category,
            'posts' => $postsPagination,
        ]);
    }

    #[Route('/articles/{id}', name: 'app_post_show', requirements: ['id' => '\d+'])]
    public function show(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$post->isApproved() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Cet article n est pas encore valide.');
        }

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        $user = $this->getUser();

        if (!$user instanceof User && $form->isSubmitted()) {
            $this->addFlash('error', 'Vous devez etre connecte pour publier un commentaire.');

            return $this->redirectToRoute('app_login');
        }

        if ($user instanceof User && $form->isSubmitted() && $form->isValid()) {
            $comment->setAuthor($user);
            $comment->setPost($post);
            $comment->setStatus('pending');
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire ajouté et en attente de validation.');

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $form,
        ]);
    }

    #[Route('/articles/{id}/like', name: 'app_post_like_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleLike(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        PostLikeRepository $postLikeRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez etre connecte pour liker un article.');

            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('toggle_like_'.$post->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $existingLike = $postLikeRepository->findOneByPostAndUser($post, $user);
        if ($existingLike instanceof PostLike) {
            $entityManager->remove($existingLike);
            $this->addFlash('success', 'Like retire.');
        } else {
            $like = (new PostLike())
                ->setPost($post)
                ->setUser($user);
            $entityManager->persist($like);
            $this->addFlash('success', 'Article like.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }
}
