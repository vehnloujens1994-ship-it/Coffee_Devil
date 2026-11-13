<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminOrderController extends AbstractController
{
    private const STATUSES = ['pending', 'preparing', 'out for delivery', 'completed', 'cancelled'];

    #[Route('', name: 'app_admin_orders')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $view = $request->query->get('view') === 'completed' ? 'completed' : 'active';
        $op = $view === 'completed' ? '=' : '!=';

        $orders = $em->createQuery(
            "SELECT o, u, oi, m FROM App\Entity\Order o
             JOIN o.user u LEFT JOIN o.orderItems oi LEFT JOIN oi.menuItem m
             WHERE o.status $op 'completed' ORDER BY o.createdAt DESC"
        )->getResult();

        $count = fn (string $dql) => (int) $em->createQuery($dql)->getSingleScalarResult();

        return $this->render('admin/orders.html.twig', [
            'orders' => $orders,
            'view' => $view,
            'statuses' => self::STATUSES,
            'total' => $count('SELECT COUNT(o.id) FROM App\Entity\Order o'),
            'pending' => $count("SELECT COUNT(o.id) FROM App\Entity\Order o WHERE o.status = 'pending'"),
            'revenue' => (float) $em->createQuery("SELECT COALESCE(SUM(o.total), 0) FROM App\Entity\Order o WHERE o.status != 'cancelled'")->getSingleScalarResult(),
            'customers' => $count("SELECT COUNT(u.id) FROM App\Entity\User u WHERE u.role = 'customer'"),
        ]);
    }

    #[Route('/orders/status', name: 'app_admin_order_status', methods: ['POST'])]
    public function status(Request $request, EntityManagerInterface $em): Response
    {
        $view = $request->request->get('view') === 'completed' ? 'completed' : 'active';

        if ($this->isCsrfTokenValid('admin_order', $request->request->getString('_token'))) {
            $order = $em->find(Order::class, $request->request->getInt('order_id'));
            $status = $request->request->getString('status');
            if ($order && in_array($status, self::STATUSES, true)) {
                $order->setStatus($status);
                $em->flush();
                $this->addFlash('success', 'Order status updated.');
            }
        }

        return $this->redirectToRoute('app_admin_orders', ['view' => $view]);
    }
}