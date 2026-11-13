<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'app_admin_users')]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $em->getRepository(User::class)->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/role', name: 'app_admin_user_role', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function role(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $role = $request->request->getString('role');
        $isSelf = $user->getUserIdentifier() === $this->getUser()->getUserIdentifier();

        if (
            !$isSelf
            && $this->isCsrfTokenValid('user_role', $request->request->getString('_token'))
            && in_array($role, ['customer', 'staff', 'admin'], true)
        ) {
            $user->setRole($role);
            $em->flush();
            $this->addFlash('success', $user->getName() . ' is now ' . $role . '.');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}