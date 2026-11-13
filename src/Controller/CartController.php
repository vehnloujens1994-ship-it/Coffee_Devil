<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(CartService $cart): Response
    {
        return $this->render('cart/index.html.twig', $cart->details());
    }

    #[Route('/add-to-cart', name: 'app_add_to_cart', methods: ['POST'])]
    public function add(Request $request, CartService $cart): Response
    {
        if (!$this->isCsrfTokenValid('cart', $request->request->getString('_token'))) {
            return $this->redirectToRoute('app_menu');
        }

        $id = $request->request->getInt('menu_item_id');
        $qty = max(1, $request->request->getInt('quantity', 1));

        if (!$this->getUser()) {
            // Remember the add, send them to log in, then back to the menu
            $session = $request->getSession();
            $session->set('pending_cart_add', ['menu_item_id' => $id, 'quantity' => $qty]);
            $session->set('_security.main.target_path', $this->generateUrl('app_menu', ['added' => 1], UrlGeneratorInterface::ABSOLUTE_URL));

            return $this->redirectToRoute('app_auth');
        }

        $cart->add($id, $qty);

        return $this->redirectToRoute('app_menu', ['added' => 1]);
    }

    #[Route('/update-cart', name: 'app_update_cart', methods: ['POST'])]
    public function update(Request $request, CartService $cart): Response
    {
        if ($this->isCsrfTokenValid('cart', $request->request->getString('_token'))) {
            $id = $request->request->getInt('menu_item_id');
            if ($request->request->getString('action') === 'remove') {
                $cart->remove($id);
            } else {
                $cart->setQuantity($id, $request->request->getInt('quantity', 1));
            }
        }

        return $this->redirectToRoute('app_cart');
    }
}