<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));
            $user->setRoles(['ROLE_USER']);
            $user->setIsActive(false);
            $user->setActivationToken(bin2hex(random_bytes(32)));

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $form->get('email')->addError(new FormError('Cet email est deja utilise.'));

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $activationLink = $this->generateUrl('app_activate_account', [
                'token' => $user->getActivationToken(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new Email())
                ->from('noreply@blog.local')
                ->to((string) $user->getEmail())
                ->subject('Activez votre compte')
                ->html($this->renderView('emails/account_activation.html.twig', [
                    'user' => $user,
                    'activationLink' => $activationLink,
                ]));

            $mailer->send($email);

            $this->addFlash('success', 'Compte créé. Vérifiez votre email pour activer votre compte.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/activation/{token}', name: 'app_activate_account')]
    public function activate(string $token, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['activationToken' => $token]);
        if (!$user) {
            $this->addFlash('error', 'Lien d\'activation invalide ou expiré.');

            return $this->redirectToRoute('app_login');
        }

        $user->setIsActive(true);
        $user->setActivationToken(null);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Compte activé. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }
}
