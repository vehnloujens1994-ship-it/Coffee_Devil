<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/signup', name: 'app_signup')]
    public function signup(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        Security $security,
        #[Autowire('%app.admin_email%')] string $adminEmail,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_post_login');
        }

        $error = null;
        $name = $email = '';

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('name'));
            $email = trim($request->request->getString('email'));
            $password = $request->request->getString('password');
            $confirm = $request->request->getString('confirm_password');

            if (!$this->isCsrfTokenValid('signup', $request->request->getString('_csrf_token'))) {
                $error = 'Invalid form. Please try again.';
            } elseif ($name === '' || $email === '' || $password === '') {
                $error = 'Please fill in all fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } elseif ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $error = 'An account with that email already exists.';
            } else {
                $user = new User();
                $user->setName($name);
                $user->setEmail($email);
                $user->setRole(strcasecmp($email, $adminEmail) === 0 ? 'admin' : 'customer');
                $user->setPassword($hasher->hashPassword($user, $password));
                $em->persist($user);
                $em->flush();

                return $security->login($user, 'form_login', 'main');
            }
        }

        return $this->render('auth/index.html.twig', [
            'mode' => 'signup',
            'error' => $error,
            'name' => $name,
            'last_username' => $email,
        ]);
    }
}