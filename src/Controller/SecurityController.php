<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/auth', name: 'app_auth')]
    public function auth(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_post_login');
        }

        return $this->render('auth/index.html.twig', [
            'mode' => 'login',
            'error' => $authUtils->getLastAuthenticationError() ? 'Incorrect email or password.' : null,
            'last_username' => $authUtils->getLastUsername(),
        ]);
    }

    #[Route('/post-login', name: 'app_post_login')]
    public function postLogin(): Response
    {
        return $this->isGranted('ROLE_STAFF')
    ? $this->redirectToRoute('app_admin_orders')
    : $this->redirectToRoute('app_menu');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('Handled by the firewall.');
    }
}