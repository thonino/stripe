<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Invoice;
use App\Repository\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StripeController extends AbstractController
{
    #[Route('/{invoice}/stripe', name: 'stripe_checkout')]
    public function checkout(Invoice $invoice, PurchaseRepository $purchaseRepo,Request $request,EntityManagerInterface $em): Response
    {
        $privateKey = "sk_test_51KfASFJ6UeDcgi22tpt2gfmnccuOUsJUJk776ZrKJ4OMukt3cyuw7oBTBvsUJNfMgfMVmhwzkDOax6trRuEIYeLY00CdVk13BA";
        Stripe::setApiKey($privateKey);
        $purchaseCriteria = ["invoice" => $invoice,];
        $purchases = $purchaseRepo->findBy($purchaseCriteria);
        $lineItems = [];
        foreach ($purchases as$purchase){
            $item = [
                "price_data"=>[
                    "currency"=>"eur",
                    "product_data"=> [
                        "name" => $purchase->getProduct()->getName(),
                    ],
                    "unit_amount"=> 100*$purchase->getUniPrice(),
                ],
                "quantity"=>$purchase->getQuantity(),
            ];
            $lineItems[] = $item;
        }

        $successRoute = $this->generateUrl('stripe_valid_payment',[
            "_locale"=>$request->getLocale(),
            "invoice"=>$invoice->getId(),
            "stripeSuccessKey"=>$invoice->getStripeSuccessKey(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $cancelRoute = $this->generateUrl('stripe_cancel_payment',[
            "_locale"=>$request->getLocale(),
            "invoice"=>$invoice->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $stripeSession = \Stripe\Checkout\Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'payment_method_types'=> ['card'],
            'success_url' => $successRoute,
            'cancel_url' => $cancelRoute,
        ]);
        
        $invoice ->setPiStripe($stripeSession->payment_intent);
        $em->flush($invoice);
        return $this->redirect($stripeSession->url, 303);
    }
    #[Route('/stripe/{invoice}/reussi/{stripeSuccessKey}', name: 'stripe_valid_payment')]
    public function success(Invoice $invoice, string $stripeSuccessKey, SessionInterface $session, PurchaseRepository $purchaseRepo): Response
    {
        if ($stripeSuccessKey != $invoice->getStripeSuccessKey()){
                $this->redirectToRoute("stripe_cancel_payment", [
                    'invoice'=> $invoice->getId(),
        ]);
        }
        $invoice->setPaid(true);
        $session->set('cart',  []);
        $purchaseCriteria = ["invoice" => $invoice,];
        $purchases = $purchaseRepo->findBy($purchaseCriteria);
        return $this->render('stripe/success.html.twig', [
            'invoice' => $invoice,
            'purchases' => $purchases,
        ]);
    }
    #[Route('/stripe/{invoice}/annulation', name: 'stripe_cancel_payment')]
    public function cancel(Invoice $invoice): Response
    {
        dd($invoice);
        return $this->render('stripe/cancel.html.twig');
    }
}
