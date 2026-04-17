<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
    public function index(Request $request, UserRepository $userRepository, PaginatorInterface $paginator): Response
    {
        $adminCount = $userRepository->countAdmins();
        $maxAdminCount = 3;

        $usersPagination = $paginator->paginate(
            $userRepository->createQueryBuilder('u')->orderBy('u.createdAt', 'DESC'),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/user/index.html.twig', [
            'users' => $usersPagination,
            'adminCount' => $adminCount,
            'maxAdminCount' => $maxAdminCount,
            'canPromoteAdmin' => $adminCount < $maxAdminCount,
        ]);
    }

    #[Route('/{id}/activer', name: 'app_admin_user_activate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function activate(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('activate_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        $user->setIsActive(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}/desactiver', name: 'app_admin_user_deactivate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deactivate(User $user, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        if (!$this->isCsrfTokenValid('deactivate_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true) && $user->isActive()) {
            $activeAdmins = $userRepository->countActiveAdmins();
            if ($activeAdmins <= 1) {
                $this->addFlash('error', 'Il doit toujours rester au moins un compte administrateur actif.');

                return $this->redirectToRoute('app_admin_user_index');
            }
        }

        $user->setIsActive(false);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}/supprimer', name: 'app_admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
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

        if (in_array('ROLE_ADMIN', $user->getRoles(), true) && $user->isActive()) {
            $activeAdmins = $userRepository->countActiveAdmins();
            if ($activeAdmins <= 1) {
                $this->addFlash('error', 'Suppression refusee: il doit rester au moins un compte administrateur actif.');

                return $this->redirectToRoute('app_admin_user_index');
            }
        }

        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur supprime avec succes.');

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}/promouvoir-admin', name: 'app_admin_user_promote', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function promoteToAdmin(User $user, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        if (!$this->isCsrfTokenValid('promote_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $this->addFlash('error', 'Ce compte est deja administrateur.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        $adminCount = $userRepository->countAdmins();
        if ($adminCount >= 3) {
            $this->addFlash('error', 'Limite atteinte: maximum 3 comptes administrateurs.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        $roles = $user->getRoles();
        $roles[] = 'ROLE_ADMIN';
        $user->setRoles(array_values(array_unique($roles)));
        $user->setIsActive(true);
        $entityManager->flush();

        $this->addFlash('success', 'Le compte utilisateur a ete promu administrateur.');

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}/retirer-admin', name: 'app_admin_user_demote', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function demoteFromAdmin(User $user, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        if (!$this->isCsrfTokenValid('demote_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $this->addFlash('error', 'Ce compte n est pas administrateur.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        if ($user->isActive()) {
            $activeAdmins = $userRepository->countActiveAdmins();
            if ($activeAdmins <= 1) {
                $this->addFlash('error', 'Action refusee: il doit rester au moins un compte administrateur actif.');

                return $this->redirectToRoute('app_admin_user_index');
            }
        }

        $roles = array_values(array_filter(
            $user->getRoles(),
            static fn (string $role): bool => 'ROLE_ADMIN' !== $role
        ));
        $user->setRoles($roles);
        $entityManager->flush();

        $this->addFlash('success', 'Les droits administrateur ont ete retires.');

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/commentaires', name: 'app_admin_comment_index')]
    public function comments(Request $request, CommentRepository $commentRepository, PaginatorInterface $paginator): Response
    {
        $commentsPagination = $paginator->paginate(
            $commentRepository->createAdminModerationQueryBuilder(),
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('admin/comment/index.html.twig', [
            'comments' => $commentsPagination,
        ]);
    }

    #[Route('/commentaires/{id}/valider', name: 'app_admin_comment_validate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validateComment(\App\Entity\Comment $comment, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('validate_comment_'.$comment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_admin_comment_index');
        }

        if ('pending' !== $comment->getStatus()) {
            $this->addFlash('error', 'Seuls les commentaires en attente peuvent etre valides.');

            return $this->redirectToRoute('app_admin_comment_index');
        }

        $comment->setStatus('approved');
        $entityManager->flush();
        $this->addFlash('success', 'Commentaire valide avec succes.');

        return $this->redirectToRoute('app_admin_comment_index');
    }

    #[Route('/commentaires/{id}/rejeter', name: 'app_admin_comment_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rejectComment(\App\Entity\Comment $comment, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('reject_comment_'.$comment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_admin_comment_index');
        }

        if ('pending' !== $comment->getStatus()) {
            $this->addFlash('error', 'Seuls les commentaires en attente peuvent etre rejetes.');

            return $this->redirectToRoute('app_admin_comment_index');
        }

        $comment->setStatus('rejected');
        $entityManager->flush();
        $this->addFlash('success', 'Commentaire rejete.');

        return $this->redirectToRoute('app_admin_comment_index');
    }
}
