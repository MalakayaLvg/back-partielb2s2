<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["order"])]
    private ?int $quantity = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'cartItems')]
    #[Groups(["order"])]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?Order $theOrder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getTheOrder(): ?Order
    {
        return $this->theOrder;
    }

    public function setTheOrder(?Order $theOrder): static
    {
        $this->theOrder = $theOrder;

        return $this;
    }
}
