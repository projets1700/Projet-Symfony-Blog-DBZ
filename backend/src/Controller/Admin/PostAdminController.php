<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/articles')]
#[IsGranted('ROLE_ADMIN')]
class PostAdminController extends AbstractController
{
    #[Route('', name: 'app_admin_post_index')]
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('admin/post/index.html.twig', [
            'posts' => $postRepository->findLatest(),
        ]);
    }

    #[Route('/nouveau', name: 'app_admin_post_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $post->setAuthor($user);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form->get('imageFile')->getData(), $post, $slugger);
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_post_index');
        }

        return $this->render('admin/post/form.html.twig', [
            'form' => $form,
            'mode' => 'Créer',
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_post_edit', requirements: ['id' => '\d+'])]
    public function edit(Post $post, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form->get('imageFile')->getData(), $post, $slugger);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_post_index');
        }

        return $this->render('admin/post/form.html.twig', [
            'form' => $form,
            'mode' => 'Modifier',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_post_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_post_'.$post->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_post_index');
    }

    private function handleImageUpload(?UploadedFile $imageFile, Post $post, SluggerInterface $slugger): void
    {
        if (!$imageFile instanceof UploadedFile) {
            return;
        }

        $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/posts';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

        try {
            $imageFile->move($uploadDir, $newFilename);
        } catch (FileException) {
            throw $this->createAccessDeniedException('Impossible de televerser cette image.');
        }

        $post->setPicture('/uploads/posts/'.$newFilename);
    }
}
