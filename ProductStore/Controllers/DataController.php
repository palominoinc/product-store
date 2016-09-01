<?php

/*
 *
 */

namespace ProductStore\Controllers;

use BaseController;
use \WebpalCore\Controllers\Core as WebpalCore;
use Input;
use \WebpalCore\Source\Services\WebPalResponse;
use Session;
use View;
use Redirect;
use \ProductStore\Models\Productstorecart;

class DataController extends BaseController
{

  /*
  * queries a store by certain criteria and renders results as a view
  */
  public function search($storeid = null) {
    $q = Input::get('q');
    $xpath = "//product-store";
    if ($storeid) $xpath .= "[@id='$storeid']";
    $params['q'] = strtolower($q);
    $template = 'product-store-search';
    $params['use-cache'] = false;
    
    return WebPalResponse::callTemplate($xpath, $template, $params);

  }
  
  public function updateCart() {
    $cart = $this->cart();
    foreach ($cart->cartItems as $item) {
      $qty = 0 + Input::get('qty_' . $item->id);
      $qty = (int) $qty;
      $qty = ($qty > 0) ? $qty: -$qty;
      $cart->setItemQuantity($item, $qty );
    }
    return Redirect::to('/product-store/cart');
  }
  
  public function addToCart() {
    $cart = $this->cart();
    if ($item = $cart->getItem(['skucode' => Input::get('skucode')])) {
      // $cart->setItemQuantity($item, $item->quantity + 1);
      $item->quantity = $item->quantity + 1;
      $item->save();
      $cart->save();
    } else {
      $cart->addItem(Input::all());  
    }
    Session::put('cart_itemcount', $cart->itemcount());
    return $this->showCart();
  }
  
  public function removeItem($id) {
    $cart = $this->cart();
    $item = $cart->cartItems()->find($id);
    if ($item) $item->delete();
    Session::put('cart_itemcount', $cart->itemcount());
    return $this->showCart();
  }
  
  public function clearCart() {
    $cart = $this->cart();
    if ($cart) $cart->delete();
    Session::forget('cart_id');
    return $this->showCart();
  }

  private function cart() {
    if (! Session::has('cart_id')) {
      $cart = new Productstorecart;
      $cart->save();
      Session::put('cart_id', $cart->id);
      Session::put('cart_itemcount', 0);
    }
    $cart = Productstorecart::find(Session::get('cart_id'));
    return $cart;
  }
  
  public function showCart() {
    $cart = $this->cart();
    $data['cart_id'] = $cart->id;
    $data['cart'] = $cart;
    return View::make('ProductStore::showCart', $data);
  }
  
  public function checkout() {
    $cart = $this->cart();
    if (! $cart->cartItems()) return $this->showCart();
    $data['cart_id'] = $cart->id;
    $data['cart'] = $cart;
    return View::make('ProductStore::checkoutCart', $data);  
  }
  
  public function completeCheckout() {
    $cart = $this->cart();
    
    //(+) TODO: validation
    $info = Input::only(
      'companyname',
      'companyid',
      'billingaddress1',
      'billingaddress2',
      'billingtown',
      'billingpostal',
      'billingprovince',
      'contactname',
      'contactphone',
      'contactemail',
      'companyfax',
      'shippingaddress1',
      'shippingaddress2',
      'shippingtown',
      'shippingpostal',
      'shippingprovince',
      'shippingcountry'
    );
    
// ipaddress
// sessionduration

    //send email notifications, mark cart as checked out, etc.
    $cart->checkout($info); 
    
    //display receipt
    $data['cart_id'] = $cart->id;
    $data['cart'] = $cart;
    return View::make('ProductStore::checkoutComplete', $data);  
  }
  
}
