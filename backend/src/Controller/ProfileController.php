<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleProfilePictureUpload($form->get('profilePictureFile')->getData(), $user, $slugger);
            $user->setUpdatedAt(new \DateTimeImmutable());

            try {
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $form->get('email')->addError(new FormError('Cet email est deja utilise.'));

                return $this->render('profile/index.html.twig', [
                    'profileForm' => $form,
                    'user' => $user,
                ]);
            }

            $this->addFlash('success', 'Profil mis a jour avec succes.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'profileForm' => $form,
            'user' => $user,
        ]);
    }

    private function handleProfilePictureUpload(?UploadedFile $imageFile, User $user, SluggerInterface $slugger): void
    {
        if (!$imageFile instanceof UploadedFile) {
            return;
        }

        $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/profiles';
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

        $user->setProfilePicture('/uploads/profiles/'.$newFilename);
    }
}
