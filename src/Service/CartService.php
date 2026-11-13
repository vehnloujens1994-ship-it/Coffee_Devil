<?php

namespace App\Service;

use App\Entity\MenuItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $em,
    ) {
    }

    /** @return array<int,int> menu_item_id => quantity */
    public function get(): array
    {
        return $this->requestStack->getSession()->get('cart', []);
    }

    public function count(): int
    {
        return array_sum($this->get());
    }

    public function add(int $menuItemId, int $quantity): bool
    {
        if (!$this->em->getRepository(MenuItem::class)->find($menuItemId)) {
            return false;
        }
        $cart = $this->get();
        $cart[$menuItemId] = ($cart[$menuItemId] ?? 0) + $quantity;
        $this->requestStack->getSession()->set('cart', $cart);

        return true;
    }

    public function setQuantity(int $menuItemId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($menuItemId);
            return;
        }
        $cart = $this->get();
        $cart[$menuItemId] = min($quantity, 20);
        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function remove(int $menuItemId): void
    {
        $cart = $this->get();
        unset($cart[$menuItemId]);
        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function clear(): void
    {
        $this->requestStack->getSession()->remove('cart');
    }

    /** @return array{items: list<array>, total: float} */
    public function details(): array
    {
        $cart = $this->get();
        $items = [];
        $total = 0.0;

        if ($cart) {
            $menuItems = $this->em->getRepository(MenuItem::class)->findBy(['id' => array_keys($cart)]);
            foreach ($menuItems as $m) {
                $qty = (int) $cart[$m->getId()];
                if ($qty < 1) {
                    continue;
                }
                $line = (float) $m->getPrice() * $qty;
                $total += $line;
                $items[] = [
                    'id' => $m->getId(),
                    'name' => $m->getName(),
                    'price' => $m->getPrice(),
                    'quantity' => $qty,
                    'lineTotal' => $line,
                ];
            }
        }

        return ['items' => $items, 'total' => $total];
    }
}