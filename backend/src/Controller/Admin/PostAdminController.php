<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
    public function index(Request $request, PostRepository $postRepository, PaginatorInterface $paginator): Response
    {
        $postsPagination = $paginator->paginate(
            $postRepository->createLatestQueryBuilder(false),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/post/index.html.twig', [
            'posts' => $postsPagination,
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
            $uploadedImage = $form->get('imageFile')->getData();
            $uploadSucceeded = $this->handleImageUpload($uploadedImage, $post, $slugger);
            $post->setIsApproved(false);
            $entityManager->persist($post);
            $entityManager->flush();
            if ($uploadedImage instanceof UploadedFile) {
                if ($uploadSucceeded) {
                    $this->addFlash('success', 'Image enregistree avec succes.');
                } else {
                    $this->addFlash('error', 'L article a ete enregistre, mais l image n a pas pu etre importee.');
                }
            }
            $this->addFlash('success', 'Article enregistre en brouillon. Cliquez sur Publier pour le rendre visible.');

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
            $uploadedImage = $form->get('imageFile')->getData();
            $uploadSucceeded = $this->handleImageUpload($uploadedImage, $post, $slugger);
            $entityManager->flush();
            if ($uploadedImage instanceof UploadedFile) {
                if ($uploadSucceeded) {
                    $this->addFlash('success', 'Image mise a jour avec succes.');
                } else {
                    $this->addFlash('error', 'Les modifications sont enregistrees, mais l image n a pas pu etre importee.');
                }
            }

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

    #[Route('/{id}/valider', name: 'app_admin_post_validate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validate(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('validate_post_'.$post->getId(), (string) $request->request->get('_token'))) {
            $post->setIsApproved(true);
            $entityManager->flush();
            $this->addFlash('success', 'Article publie et visible pour les visiteurs/utilisateurs.');
        } else {
            $this->addFlash('error', 'Jeton de securite invalide.');
        }

        return $this->redirectToRoute('app_admin_post_index');
    }

    private function handleImageUpload(?UploadedFile $imageFile, Post $post, SluggerInterface $slugger): bool
    {
        if (!$imageFile instanceof UploadedFile) {
            return false;
        }

        $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/posts';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = (string) $slugger->slug($originalFilename);
        if ('' === $safeFilename) {
            $safeFilename = 'article-image';
        }
        if (strlen($safeFilename) > 80) {
            $safeFilename = substr($safeFilename, 0, 80);
        }
        $extension = $imageFile->guessExtension();
        $clientExtension = strtolower((string) $imageFile->getClientOriginalExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!is_string($extension) || '' === $extension || !in_array(strtolower($extension), $allowedExtensions, true)) {
            $extension = in_array($clientExtension, $allowedExtensions, true) ? $clientExtension : 'png';
        }

        $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

        try {
            $imageFile->move($uploadDir, $newFilename);
        } catch (FileException) {
            $this->addFlash('error', 'Impossible de televerser cette image. Verifiez le format et la taille du fichier.');

            return false;
        }

        $post->setPicture('/uploads/posts/'.$newFilename);

        return true;
    }
}
