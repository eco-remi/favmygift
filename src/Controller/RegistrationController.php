<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, RateLimiterFactoryInterface $registerLimiter): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Generate Math Captcha if not present
        $session = $request->getSession();
        if (!$session->has('captcha_result')) {
            $a = rand(1, 10);
            $b = rand(1, 10);
            $session->set('captcha_result', $a + $b);
            $session->set('captcha_question', "$a + $b");
        }

        if ($request->isMethod('POST')) {
            // 1. Honeypot check
            if ($request->request->get('_hp_email')) {
                // Silently fail for bots
                return $this->redirectToRoute('app_login');
            }

            // 2. Rate Limiting
            $limiter = $registerLimiter->create($request->getClientIp());
            if (false === $limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }

            // 3. Captcha Check
            $userAnswer = (int) $request->request->get('captcha');
            $correctAnswer = $session->get('captcha_result');

            // Reset captcha after attempt
            $session->remove('captcha_result');
            $session->remove('captcha_question');

            if ($userAnswer !== $correctAnswer) {
                $this->addFlash('error', 'security.captcha.error');
                return $this->redirectToRoute('app_register');
            }

            $email = $request->request->get('email');
            $plainPassword = $request->request->get('password');

            if ($email && $plainPassword) {
                $user = new User();
                $user->setEmail($email);
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/register.html.twig', [
            'captcha_question' => $session->get('captcha_question')
        ]);
    }

    #[Route('/register/check-email', name: 'app_check_email', methods: ['POST'])]
    public function checkEmail(Request $request, EntityManagerInterface $entityManager, RateLimiterFactoryInterface $registerLimiter): Response
    {
        // Rate limiting for check-email too
        $limiter = $registerLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Too many requests'], 429);
        }

        $email = $request->getPayload()->get('email');
        if (!$email) {
            return $this->json(['exists' => false]);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        return $this->json(['exists' => null !== $user]);
    }
}
