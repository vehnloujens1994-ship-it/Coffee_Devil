<?php

namespace App\Controller;

use App\Entity\MenuItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(CartService $cart): Response
    {
        $details = $cart->details();
        if (!$details['items']) {
            return $this->redirectToRoute('app_cart');
        }

        return $this->render('order/checkout.html.twig', $details);
    }

    #[Route('/place-order', name: 'app_place_order', methods: ['POST'])]
    public function place(Request $request, CartService $cart, EntityManagerInterface $em): Response
    {
        $details = $cart->details();
        if (!$details['items']) {
            return $this->redirectToRoute('app_cart');
        }

        if (!$this->isCsrfTokenValid('checkout', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid form. Please try again.');

            return $this->redirectToRoute('app_checkout');
        }

        $method = $request->request->getString('payment_method', 'cod');
        if (!in_array($method, ['cod', 'gcash'], true)) {
            $method = 'cod';
        }

        $ref = null;
        if ($method === 'gcash') {
            $ref = trim($request->request->getString('reference_code'));
            if ($ref === '' || !preg_match('/^[0-9-]+$/', $ref) || strlen($ref) > 50) {
                $this->addFlash('error', 'Please enter a valid GCash reference number (digits and dashes only).');

                return $this->redirectToRoute('app_checkout');
            }
        }

        $order = new Order();
        $order->setUser($this->getUser());
        $order->setStatus('pending');
        $order->setTotal(number_format($details['total'], 2, '.', ''));
        $order->setPaymentMethod($method);
        $order->setReferenceCode($ref);

        // One transaction: the order and all its lines are saved together or not at all
        $em->wrapInTransaction(function () use ($em, $order, $details) {
            $em->persist($order);
            foreach ($details['items'] as $line) {
                $item = new OrderItem();
                $item->setOrder($order);
                $item->setMenuItem($em->getReference(MenuItem::class, $line['id']));
                $item->setQuantity($line['quantity']);
                $item->setPriceAtOrder($line['price']);
                $em->persist($item);
            }
        });

        $cart->clear();

        return $this->redirectToRoute('app_order_success', ['id' => $order->getId()]);
    }

    #[Route('/order-success/{id}', name: 'app_order_success', requirements: ['id' => '\d+'])]
    public function success(int $id, EntityManagerInterface $em): Response
    {
        // Only the owner can see their own order confirmation
        $order = $em->getRepository(Order::class)->findOneBy(['id' => $id, 'user' => $this->getUser()]);
        if (!$order) {
            return $this->redirectToRoute('app_menu');
        }

        return $this->render('order/success.html.twig', ['order' => $order]);
    }

    #[Route('/my-orders', name: 'app_my_orders')]
    public function mine(EntityManagerInterface $em): Response
    {
        $orders = $em->createQuery(
            'SELECT o, oi, m FROM App\Entity\Order o
             LEFT JOIN o.orderItems oi LEFT JOIN oi.menuItem m
             WHERE o.user = :u ORDER BY o.createdAt DESC'
        )->setParameter('u', $this->getUser())->getResult();

        return $this->render('order/mine.html.twig', ['orders' => $orders]);
    }
}