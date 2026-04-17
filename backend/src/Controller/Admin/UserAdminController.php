<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UserAdminController extends AbstractController
{
    #[Route('', name: 'app_admin_user_index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/activer', name: 'app_admin_user_activate', requirements: ['id' => '\d+'])]
    public function activate(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsActive(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}/desactiver', name: 'app_admin_user_deactivate', requirements: ['id' => '\d+'])]
    public function deactivate(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsActive(false);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}/supprimer', name: 'app_admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte administrateur.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        if ($user->getPosts()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cet utilisateur car il possede des articles.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur supprime avec succes.');

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/commentaires', name: 'app_admin_comment_index')]
    public function comments(CommentRepository $commentRepository): Response
    {
        return $this->render('admin/comment/index.html.twig', [
            'comments' => $commentRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/commentaires/{id}/valider', name: 'app_admin_comment_validate', requirements: ['id' => '\d+'])]
    public function validateComment(\App\Entity\Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $comment->setStatus('approved');
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_comment_index');
    }

    #[Route('/commentaires/{id}/rejeter', name: 'app_admin_comment_reject', requirements: ['id' => '\d+'])]
    public function rejectComment(\App\Entity\Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $comment->setStatus('deleted');
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_comment_index');
    }
}
