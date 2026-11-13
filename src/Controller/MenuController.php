<?php

namespace App\Controller;

use App\Entity\MenuItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MenuController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(EntityManagerInterface $em): Response
    {
    $featured = $em->getRepository(MenuItem::class)->findBy([], ['id' => 'ASC'], 4);

    return $this->render('home/index.html.twig', ['featured' => $featured]);
    }

    #[Route('/menu', name: 'app_menu')]
    public function index(EntityManagerInterface $em): Response
    {
        $items = $em->getRepository(MenuItem::class)
            ->findBy([], ['category' => 'ASC', 'name' => 'ASC']);

        return $this->render('menu/index.html.twig', ['items' => $items]);
    }
}