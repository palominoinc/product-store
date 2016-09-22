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
use Validator;
Use Mail;
use Response;
use \ProductStore\Models\Productstorecart;
use Log;

class DataController extends BaseController
{

  /*
  * exports all stores as CSV
  */
  public function exportAll($storeid = null) {
    $template = 'product-store-export-csv';
    $xpath = '/web/pages';
    $status = 200;
    $params = ['output' => 'text', 
               'use-cache' => false,
              ];
    $headers = array(
      'Content-Type' => 'text/plain; charset=utf-8',
      'Content-Disposition' => 'attachment',
      'filename' => 'product-store.csv',
      'Accept-Ranges' => 'bytes',
      'Content-Transfer-Encoding' => 'binary',
      'Cache-Control' => 'private',
      'Pragma' => 'private',
      'Expires' => date('Y-m-d hh:ii:ss T'),
    );
    // $headers = array();
    return WebPalResponse::callTemplate($xpath, $template, $params, [], $status, $headers);
  }

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
    Session::flash('message', "Item added to cart");
    return Redirect::back();// $this->showCart();
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
  public function clearCartFinal() {
    $cart = $this->cart();
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
    // foreach ($cart->cartItems as $item) {
    //   $qty = 0 + Input::get('qty_' . $item->id);
    //   $qty = (int) $qty;
    //   $qty = ($qty > 0) ? $qty: -$qty;
    //   $cart->setItemQuantity($item, $qty );
    // }
    if (! $cart->cartItems()) return $this->showCart();
    $data['cart_id'] = $cart->id;
    $data['cart'] = $cart;
    return View::make('ProductStore::checkoutCart', $data); 
    // return Redirect('/product-store/checkout-cart')->with($data);
  }

  public function email() {
    $cart = $this->cart();
    $data = Input::all();
    $email = $data['contactemail'];
    $name = $data['contactname'];
    $data['cart_id'] = $cart->id;
    $data['cart'] = $cart;
    $subject = 'BND Order Confirmation #'.substr(sha1($cart->id), 0, 6);
    Mail::send('ProductStore::checkoutEmail', $data, function($message) use ($email , $name, $subject)
               {
      $message->to($email, $name)->subject($subject)
        ->from('donotreply@bndinc.com')
        ->bcc('customerservice@bndinc.com');
      //  ->bcc('sinthu@palominosys.com'); // needs to be changed to the business email
    });
    if(count(Mail::failures()) > 0 ){       
      $cart->emailsubmitted=false;
    }else{
      $cart->emailsubmitted=true;  
    }
    $cart->save();
  }

  public function completeCheckout() {
    $cart = $this->cart();

    //(+) TODO: validation
    $data = Input::all();
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

    $rules = array('companyname' =>'required', 'shippingaddress1' =>'required', 'shippingpostal' =>'required', 'shippingtown' =>'required', 'contactname' =>'required', 'contactphone' =>'required', 'contactemail' => 'required|email');
    $messages = array(
      'companyname.required'=> 'The company name field is required',
      'shippingaddress1.required'=> 'The address field is required',
      'shippingpostal.required'=> 'The postal code field is required',
      'shippingtown.required'=> 'The city field is required',
      'contactname.required'=> 'The contact name field is required',
      'contactphone.required'=> 'The contact phone field is required',
      'contactemail.required'=>"The contact email field is required",
      'contactemail.email'=>"The contact email is not valid"
    );
    $validator = Validator::make(array("companyname" => $data['companyname'], "shippingaddress1" => $data['shippingaddress1'], "shippingpostal" => $data['shippingpostal'], "shippingtown" => $data['shippingtown'], "contactname" => $data['contactname'], "contactphone" => $data['contactphone'], "contactemail" => $data['contactemail']),
                                 $rules, $messages);
    if ($validator->fails()){
      $data['cart_id'] = $cart->id;
      $data['cart'] = $cart;
      return Redirect::back()->withInput()->withErrors($validator);
    }
    else{
      // ipaddress
      // sessionduration

      //send email notifications, mark cart as checked out, etc.
      $cart->checkout($info);
      $this->email();
      //display receipt
      $data['cart_id'] = $cart->id;
      $data['cart'] = $cart;
      $infor = ['companyname'=>$data['companyname'], 'shippingaddress1'=>$data['shippingaddress1'], 'contactname'=>$data['contactname'], 'contactphone'=>$data['contactphone'], 'contactemail'=>$data['contactemail']];
      $data['infor'] = $infor;
      $this->clearCartFinal();
      return View::make('ProductStore::checkoutComplete', $data);
    }
  }


}
