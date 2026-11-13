<?php

namespace App\Controller;

use App\Entity\MenuItem;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/menu')]
class AdminMenuController extends AbstractController
{
    #[Route('', name: 'app_admin_menu')]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('admin/menu_list.html.twig', [
            'items' => $em->getRepository(MenuItem::class)->findBy([], ['category' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_menu_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_admin_menu_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function form(Request $request, EntityManagerInterface $em, ?int $id = null): Response
    {
        $item = $id ? $em->find(MenuItem::class, $id) : new MenuItem();
        if (!$item) {
            throw $this->createNotFoundException();
        }
        $error = null;

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('name'));
            $category = trim($request->request->getString('category'));
            $description = trim($request->request->getString('description'));
            $price = $request->request->getString('price');

            if (!$this->isCsrfTokenValid('menu_form', $request->request->getString('_token'))) {
                $error = 'Invalid form. Please try again.';
            } elseif ($name === '' || $category === '' || !is_numeric($price) || $price < 0) {
                $error = 'Please fill in the name, category, and a valid price.';
            } else {
                $item->setName($name);
                $item->setCategory($category);
                $item->setDescription($description !== '' ? $description : null);
                $item->setPrice(number_format((float) $price, 2, '.', ''));

                $file = $request->files->get('image');
                if ($file) {
                    $ext = $file->guessExtension();
                    if (!$file->isValid() || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        $error = 'Image must be a JPG, PNG, or WEBP file.';
                    } else {
                        $newName = uniqid('menu_', true) . '.' . $ext;
                        $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/menu', $newName);
                        $item->setImage('uploads/menu/' . $newName);
                    }
                }

                if (!$error) {
                    $em->persist($item);
                    $em->flush();
                    $this->addFlash('success', 'Menu item saved.');

                    return $this->redirectToRoute('app_admin_menu');
                }
            }
        }

        return $this->render('admin/menu_form.html.twig', ['item' => $item, 'error' => $error, 'is_edit' => (bool) $id]);
    }

    #[Route('/{id}/delete', name: 'app_admin_menu_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(MenuItem $item, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('menu_delete_' . $item->getId(), $request->request->getString('_token'))) {
            $image = $item->getImage();
            try {
                $em->remove($item);
                $em->flush();
            } catch (ForeignKeyConstraintViolationException) {
                $this->addFlash('error', "Can't delete this item, it's part of existing orders.");

                return $this->redirectToRoute('app_admin_menu');
            }

            // Only delete files uploaded through the admin form, never the seed photos
            if ($image && str_starts_with($image, 'uploads/')) {
                $path = $this->getParameter('kernel.project_dir') . '/public/' . $image;
                if (is_file($path)) {
                    unlink($path);
                }
            }
            $this->addFlash('success', 'Menu item deleted.');
        }

        return $this->redirectToRoute('app_admin_menu');
    }
}