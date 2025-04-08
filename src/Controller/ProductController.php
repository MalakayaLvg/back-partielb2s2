<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\QrCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
final class ProductController extends AbstractController
{
    private QrCodeGenerator $qrCodeGenerator;

    public function __construct(QrCodeGenerator $qrCodeService)
    {
        $this->qrCodeGenerator = $qrCodeService;
    }

    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, QrCodeGenerator $qrCodeGenerator): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $qrCodeData = json_encode([
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice()
            ]);
            $qrCode = $this->qrCodeGenerator->generateQrCode($qrCodeData);
            $product->setQrCode($qrCode);

            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès et QR code généré.');

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/download-pdf', name: 'app_product_download_pdf', methods: ['GET'])]
    public function downloadPdf(Product $product): Response
    {
        $dompdf = new Dompdf();
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Permet de charger les images distantes
        $dompdf->setOptions($options);

        $html = "
        <h1 style='text-align: center;'>QR Code pour le produit</h1>
        <table style='width: 100%; border-collapse: collapse; text-align: left;'>
            <tr>
                <th style='border: 1px solid #000; padding: 8px;'>ID</th>
                <td style='border: 1px solid #000; padding: 8px;'>{$product->getId()}</td>
            </tr>
            <tr>
                <th style='border: 1px solid #000; padding: 8px;'>Nom</th>
                <td style='border: 1px solid #000; padding: 8px;'>{$product->getName()}</td>
            </tr>
            <tr>
                <th style='border: 1px solid #000; padding: 8px;'>Prix</th>
                <td style='border: 1px solid #000; padding: 8px;'>{$product->getPrice()}</td>
            </tr>
            <tr>
                <th style='border: 1px solid #000; padding: 8px;'>QR Code</th>
                <td style='border: 1px solid #000; padding: 8px; text-align: center;'>
                    <img src='{$product->getQrCode()}' alt='QR Code' style='width: 300px; height: 300px;' />
                </td>
            </tr>
        </table>
    ";

        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="product-{$product->getId()}-qr-code.pdf"',
        ]);
    }
}
