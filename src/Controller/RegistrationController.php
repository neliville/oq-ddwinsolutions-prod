<?php

namespace App\Controller;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Collaboration\CollaborationSession;
use App\Collaboration\CollaborationToken;
use App\Collaboration\InvitationStatus;
use App\Collaboration\UserInvitationService;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserInvitationRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerService $mailerService,
        private readonly LoggerInterface $logger,
        private readonly TrackingEventRecorder $trackingEventRecorder,
        private readonly UserInvitationRepository $userInvitationRepository,
        private readonly UserInvitationService $userInvitationService,
        private readonly Security $security,
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_index');
        }

        $user = new User();
        if ($request->isMethod('GET')) {
            $plain = $request->query->getString('invitation');
            if ($plain !== '') {
                $inv = $this->userInvitationRepository->findByTokenHash(CollaborationToken::hashPlain($plain));
                if ($inv !== null
                    && $inv->getStatus() === InvitationStatus::ENVOYEE
                    && $inv->getExpiresAt() >= new \DateTimeImmutable()) {
                    if ($request->hasSession()) {
                        $request->getSession()->set(CollaborationSession::INVITATION_PLAIN_TOKEN, $plain);
                    }
                    $user->setEmail($inv->getEmail());
                }
            }
        }
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Sauvegarder l'utilisateur
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            if ($request->hasSession()) {
                $session = $request->getSession();
                $invitePlain = $session->get(CollaborationSession::INVITATION_PLAIN_TOKEN);
                if (\is_string($invitePlain) && $invitePlain !== '') {
                    $this->userInvitationService->tryAccept($user, $invitePlain);
                    $session->remove(CollaborationSession::INVITATION_PLAIN_TOKEN);
                }
            }

            $email = $user->getEmail();
            $domain = \is_string($email) && str_contains($email, '@') ? substr(strrchr($email, '@'), 1) : null;
            $this->trackingEventRecorder->record(
                TrackingEventType::ACCOUNT_CREATED,
                $domain !== null && $domain !== '' ? ['email_domain' => substr($domain, 0, 120)] : [],
                $user,
                null,
                null,
                'web',
            );

            $welcomeEmailSent = false;
            try {
                $this->mailerService->sendWelcomeEmail($user);
                $welcomeEmailSent = true;
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'envoi de l\'email de bienvenue', ['exception' => $e]);
            }

            $this->addFlash(
                'success',
                $welcomeEmailSent
                    ? 'Votre compte a été créé avec succès ! Un email de bienvenue vous a été envoyé.'
                    : 'Votre compte a bien été créé. Vous êtes maintenant connecté à votre espace.'
            );

            $loginResponse = $this->security->login($user, 'form_login', 'main');

            return $loginResponse ?? $this->redirectToRoute('app_dashboard_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/bienvenue', name: 'app_bienvenue', methods: ['GET'])]
    public function bienvenue(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_index');
        }

        return $this->render('registration/bienvenue.html.twig');
    }
}

