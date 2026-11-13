<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'menu_item_id', nullable: false, onDelete: 'RESTRICT')]
    private ?MenuItem $menuItem = null;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(name: 'price_at_order', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $priceAtOrder = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPriceAtOrder(): ?string
    {
        return $this->priceAtOrder;
    }

    public function setPriceAtOrder(string $priceAtOrder): static
    {
        $this->priceAtOrder = $priceAtOrder;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getMenuItem(): ?MenuItem
    {
        return $this->menuItem;
    }

    public function setMenuItem(?MenuItem $menuItem): static
    {
        $this->menuItem = $menuItem;

        return $this;
    }
}