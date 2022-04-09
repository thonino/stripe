<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\Purchase;
use App\Form\InvoiceType;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InvoiceController extends AbstractController
{
    #[Route('/invoice', name: 'app_invoice')]
    public function index(): Response
    {
        // compte stripe
        // récupération données de livraison
        // créer les données deans la data base
        // faire payer
        // vider le panier et remercier

        return $this->render('invoice/index.html.twig', [
            'controller_name' => 'InvoiceController',
        ]);
    }

    #[Route('/facture/adresse', name: 'app_invoice_new', methods: ['GET', 'POST'])]
    public function new(Request $request,SessionInterface $session,ProductRepository $productRepo): Response
    {
        $invoice = new Invoice();
        $user = $this->getUser();
        if ($user) {
            $invoice->setFirstname($user->getFirstname())
            ->setLastname($user->getLastname());
        }
        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);
        $fullCart = [];
        $total = 0;
        $cart = $session->get('cart', []);
        foreach($cart as $id =>$qty){
            $product = $productRepo->find($id);  
            $fullCart[]= ['product' => $product,'qty' => $qty,];
            $total += $product->getPrice()*$qty;
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $invoice->setTotalPrice($total)
                    ->setPaid(false)
                    ->setStripeSuccessKey(uniqid());
            $entityManager->persist($invoice);
            foreach($cart as $id =>$qty){
                $product = $productRepo->find($id);
                $purchase = new Purchase;
                $purchase->setInvoice($invoice)
                ->setProduct($product)
                ->setUniPrice($product->getPrice())
                ->setQuantity($qty);
                $entityManager->persist($purchase);
            }
            dd($invoice);
            $entityManager->flush();
            return $this->redirectToRoute('tripe_checkout', ["invoice"=> $invoice->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('invoice/new.html.twig', [
            'invoice' => $invoice,
            'form' => $form,
            'cartProducts'=>$fullCart,
            'total' =>$total,
        ]);
    }
}
