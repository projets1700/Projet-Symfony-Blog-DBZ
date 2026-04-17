<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Form\CommentType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostController extends AbstractController
{
    #[Route('/articles', name: 'app_post_index')]
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findLatest(),
        ]);
    }

    #[Route('/articles/{id}', name: 'app_post_show', requirements: ['id' => '\d+'])]
    public function show(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
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
}
