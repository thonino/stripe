<?php
namespace App\Controller;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
#[Route(['en' => '/cart','fr' => '/panier',])]
class CartController extends AbstractController
{
    // Fonction ADD
    #[Route(['en' => '/{product}/add','fr' => '/{product}/ajouter',], name: 'cart_add')]
    public function add(Product $product, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $id = $product->getId();
        if (array_key_exists($id, $cart)){  $cart[$id]++;}
        else {  $cart[$id] =1; }
        $session->set('cart', $cart);
        return $this->redirectToRoute('cart_show', [ 'id'=>$id,]);
    }
    // Fonction LESS
    #[Route(['en' => '/{product}/less','fr' => '/{product}/moins',], name: 'cart_less')]
    public function less(Product $product, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $id = $product->getId();
        if (2 > $cart[$id]){unset($cart[$id]);} 
        else { $cart[$id]--;}
        $session->set('cart', $cart);
        return $this->redirectToRoute('cart_show', [ 'id'=>$id,]);
    }
    // Fonction supprimer
    #[Route(['en' => '/{product}/del','fr' => '/{product}/suprimmer',], name: 'cart_del')]
    public function remove(Product $product, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $id = $product->getId();
        unset($cart[$id]);
        $session->set('cart', $cart);
        return $this->redirectToRoute('cart_show', [ 'id'=>$id,]);
    }
    #[Route(['en' => '/show','fr' => '/voir',], name: 'cart_show')]
    public function show(SessionInterface $session, ProductRepository $productRepo): Response
    {
        $fullCart = [];
        $total = 0;
        $cart = $session->get('cart', []);
        foreach($cart as $id =>$qty){
            $product = $productRepo->find($id);  
            $fullCart[]= ['product' => $product,'qty' => $qty,];
            $total += $product->getPrice()*$qty;
        }
        return $this->render('cart/cart.html.twig', [
            'cartProducts'=>$fullCart,
            'total' =>$total,
        ]);
    }
}