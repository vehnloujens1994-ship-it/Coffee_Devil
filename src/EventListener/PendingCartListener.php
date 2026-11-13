<?php

namespace App\EventListener;

use App\Service\CartService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener]
class PendingCartListener
{
    public function __construct(private CartService $cart)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $session = $event->getRequest()->getSession();
        $pending = $session->get('pending_cart_add');
        if ($pending) {
            $session->remove('pending_cart_add');
            $this->cart->add((int) $pending['menu_item_id'], (int) $pending['quantity']);
        }
    }
}