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
  
  public function addToCart() {
    if (! Session::has('cart')) {
      $cart = new Productstorecart;
      $cart->save();
      Session::put('cart', $cart);
    }
    $cart = Session::get('cart');
    $cart->addItem(Input::all());   
    return $this->showCart();
  }

  public function showCart() {
    $data['cart'] = Session::get('cart');
    return View::make('ProductStore::showCart', $data);
  }
}
