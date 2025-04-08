<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Order;
use App\Entity\Cart;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


final class OrderController extends AbstractController
{
    #[Route('/api/order/validate', methods: ['POST'])]
    public function validateOrder(
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }


        $cart = $user->getCart();

        if (!$cart || $cart->getItems()->isEmpty()) {
            return $this->json(['error' => 'Panier vide'], 400);
        }

        $totalPrice = array_reduce($cart->getItems()->toArray(), function ($carry, $item) {
            return $carry + ($item->getProduct()->getPrice() * $item->getQuantity());
        }, 0);

        $order = new Order();
        $order->setTotalAmount($totalPrice);
        $order->setCustomer($cart->getCustomer());
        foreach ($cart->getItems() as $item) {
            $order->addItem($item);
            $item->setTheOrder($order);
        }

        $em->persist($order);
        $em->flush();

        return $this->json(['message' => 'Commande validée', 'orderId' => $order->getId()]);
    }
}
