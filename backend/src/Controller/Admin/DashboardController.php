<?php

namespace App\Controller\Admin;

use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        PostRepository $postRepository,
        CommentRepository $commentRepository
    ): Response {
        return $this->render('admin/dashboard/index.html.twig', [
            'kpis' => [
                'users' => $userRepository->count([]),
                'posts' => $postRepository->count([]),
                'pendingComments' => $commentRepository->count(['status' => 'pending']),
            ],
            'topPosts' => $postRepository->findTopArticlesByEngagement(),
        ]);
    }
}
