<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/api/cart/add', name: 'add_to_cart', methods: ['POST'])]
    public function addToCart(
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $productId = $data['productId'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        if (!$productId) {
            return $this->json(['error' => 'Product ID is required'], 400);
        }

        $product = $productRepository->find($productId);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        $cart = $user->getCart();
        if (!$cart) {
            $cart = new Cart();
            $cart->setCustomer($user);
            $entityManager->persist($cart);
        }

        $existingItem = $cart->getItems()->filter(function (CartItem $item) use ($product) {
            return $item->getProduct()->getId() === $product->getId();
        })->first();

        if ($existingItem) {

            $existingItem->setQuantity($existingItem->getQuantity() + $quantity);
        } else {

            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setCart($cart);
            $cartItem->setQuantity($quantity);

            $entityManager->persist($cartItem);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Product added to cart']);
    }

    #[Route('/api/cart', name: 'get_cart', methods: ['GET'])]
    public function getCart(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $cart = $user->getCart();
        if (!$cart || $cart->getItems()->isEmpty()) {
            return $this->json(['message' => 'Cart is empty'], 200);
        }


        $cartItems = $cart->getItems()->map(function (CartItem $item) {
            return [
                'productId' => $item->getProduct()->getId(),
                'productName' => $item->getProduct()->getName(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getProduct()->getPrice(),
            ];
        });

        return $this->json(['cartItems' => $cartItems->toArray()]);
    }

    #[Route('/api/cart/remove', name: 'remove_from_cart', methods: ['POST'])]
    public function removeFromCart(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $productId = $data['productId'] ?? null;

        if (!$productId) {
            return $this->json(['error' => 'Product ID is required'], 400);
        }

        $cart = $user->getCart();
        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], 404);
        }

        $cartItem = $cart->getItems()->filter(function (CartItem $item) use ($productId) {
            return $item->getProduct()->getId() === $productId;
        })->first();

        if (!$cartItem) {
            return $this->json(['error' => 'Product not found in cart'], 404);
        }

        $entityManager->remove($cartItem);
        $entityManager->flush();

        return $this->json(['message' => 'Product removed from cart']);
    }

    #[Route('/api/cart/clear', name: 'clear_cart', methods: ['POST'])]
    public function clearCart(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $cart = $user->getCart();
        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], 404);
        }

        foreach ($cart->getItems() as $item) {
            $entityManager->remove($item);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Cart cleared']);
    }


}
