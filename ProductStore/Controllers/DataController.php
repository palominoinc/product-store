<?php

/*
 *
 */

namespace ProductStore\Controllers;

use BaseController;
use Illuminate\Support\Facades\DB;
use \WebpalCore\Controllers\Core as WebpalCore;
use Input;
use \WebpalCore\Source\Services\WebPalResponse;
use Session;
use View;
use Redirect;
use Validator;
Use Mail;
use Response;
use Lang;
use \ProductStore\Models\Productstorecart;
use \ProductStore\Models\Productstorecartitem;
use \ProductStore\Models\Order;
use \ProductStore\Models\User; 
use \ProductStore\Models\Price;
use \ProductStore\Models\ShipAddress;
use \ProductStore\Models\BillingAddress;
use \ProductStore\Models\CustomerTax;
use \ProductStore\Models\TaxRate;
use \ProductStore\Models\InvoiceEmail;
use Log;
use Bugsnag;
use \WebpalLogin\Source\WebPalAPI\Connection; 
use Illuminate\Support\Facades\Auth;

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
    $lang = (!empty(Lang::locale())) ? Lang::locale() : 'en';
    $storeid = ($lang == 'fr') ? 'wpPrJ9rcAaU6oBXx' : 'wpPrNyFKIOawa9a1';
    $q = Input::get('q');
    $xpath = "//product-store";
    if ($storeid) $xpath .= "[@id='$storeid']";
    //$xpath .= "[@lang='{$lang}']";
    $params['q'] = strtolower($q);
    $params['qscope'] = Input::get('qscope');
    $template = 'product-store-search';
    $params['use-cache'] = false;

    Input::flash();
  
    return WebPalResponse::callTemplate($xpath, $template, $params);
    
//     $q = Input::get('q');
//     $xpath = "//product-store";
//     if ($storeid) $xpath .= "[@id='$storeid']";
//     $params['q'] = strtolower($q);
//     $params['qscope'] = Input::get('qscope');
//     $template = 'product-store-search';
//     $params['use-cache'] = false;

//     Input::flash();
//     return WebPalResponse::callTemplate($xpath, $template, $params);

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
    $data['is_logged_in'] = Connection::get()->isLoggedIn();
    if(Connection::get()->isLoggedIn()){
    $data['productquantity'] = $this->getPricexQty($cart);
    }
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
    $data['is_logged_in'] = Connection::get()->isLoggedIn();
    if(Connection::get()->isLoggedIn()){
      $data['subtotal'] = $this->getSubtotal($cart);
      $data['tax'] = $this->getTax(); 
      $data['taxAmount'] = round($this->taxAmount($cart),2);
      $data['total'] = $this->getTotal($cart);
      $data['shippingadd'] = $this->getShippingAddresses();
      $data['locationid'] = $this->getLocationID();
      $data['billing'] = $this->getBillingAddress(); 
      $data['productprices'] = $this->productPrice($cart);
      $data['productquantity'] = $this->getPricexQty($cart);
      $data['invoiceemails'] = $this->getInvoiceEmails(); 
    } 
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
      
    });
    if(count(Mail::failures()) > 0 ){       
      $cart->emailsubmitted=false;
    }else{
      $cart->emailsubmitted=true;  
    }
    $cart->save();
  }
   /*
  * Similair to previous function but used to send to a different BND email when customer is logged in in order to differentiate 
  * between logged in customers and non-logged in customers. 
  */
    public function loggedInEmail() {
    $cart = $this->cart();
    $data = Input::all();   
    if(!empty($this->getInvoiceEmails()))
    {
      $email = $data['inemail'];
     }else{
      $email = $data['contactemail'];
    }
    $name = $data['contactname'];
    $data['cart_id'] = $cart->id;
    $data['cart'] = $cart;
    $data['is_logged_in'] = Connection::get()->isLoggedIn();
    $data['subtotal'] = $this->getSubtotal($cart);
    $data['tax'] = $this->getTax(); 
    $data['taxAmount'] = number_format((float)$this->taxAmount($cart), 2, '.', '');        
    $data['total'] = $this->getTotal($cart);
    $data['productprices'] = $this->productPrice($cart);
    $data['productquantity'] = $this->getPricexQty($cart);
    $data['shipst1'] = $this->getShippingSt1($data['shipid']);
    $data['shipst2'] = $this->getShippingSt2($data['shipid']);
    $data['shipst3'] = $this->getShippingSt3($data['shipid']);
    $data['shipcity'] = $this->getShippingCity($data['shipid']);
    $data['shipprov'] = $this->getShippingProvince($data['shipid']);
    $data['shippstl'] = $this->getShippingPostal($data['shipid']);
    $data['billst1'] = $this->getBillingSt1();
    $data['billst2'] = $this->getBillingSt2();
    $data['billst3'] = $this->getBillingSt3();
    $data['billcity'] = $this->getBillingCity();
    $data['billprovince'] = $this->getBillingProvince();
    $data['billpostal'] = $this->getBillingPostal();
    $data['custid'] = Auth::user()->getCustomerID();
    $data['invoice'] = $this->getInvoiceEmails(); 
   
      
    $subject = 'BND Order Confirmation #'.substr(sha1($cart->id), 0, 6);
    Mail::send('ProductStore::checkoutEmail', $data, function($message) use ($email , $name, $subject)
               {
      $message->to($email, $name)->subject($subject)
        ->from('donotreply@bndinc.com')
        ->bcc('customerservice@bndinc.com');
    
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

    $rules = array('companyname' =>'required', 
                   'shippingaddress1' =>'required', 
                   'shippingpostal' =>'required',
                   'shippingtown' =>'required',
                   'contactname' =>'required',
                   'contactphone' =>'required',
                   'contactemail' => 'required|email'
                  );
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
  //Execute this function instead of the previous one if the user is logged in
   public function completeLogCheckout() {
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

    $rules = array('companyname' =>'required', 
                   // 'shippingaddress1' =>'required', 
                   // 'shippingpostal' =>'required',
                   // 'shippingtown' =>'required',
                   'contactname' =>'required',
                   'contactphone' =>'required'
                );
    $messages = array(
      'companyname.required'=> 'The company name field is required',
      // 'shippingaddress1.required'=> 'The address field is required',
      // 'shippingpostal.required'=> 'The postal code field is required',
      // 'shippingtown.required'=> 'The city field is required',
      'contactname.required'=> 'The contact name field is required',
      'contactphone.required'=> 'The contact phone field is required'
    );
    // $validator = Validator::make(array("companyname" => $data['companyname'], "shippingaddress1" => $data['shippingaddress1'], "shippingpostal" => $data['shippingpostal'], "shippingtown" => $data['shippingtown'], "contactname" => $data['contactname'], "contactphone" => $data['contactphone'], "contactemail" => $data['contactemail']),
    //                              $rules, $messages);
     
     $validator = Validator::make(array("companyname" => $data['companyname'], "contactname" => $data['contactname'], "contactphone" => $data['contactphone']),
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
      $this->loggedInEmail();
      //display receipt
      $data['cart_id'] = $cart->id;
      $data['cart'] = $cart;
      $this->storeTransaction($cart, $data);
      $infor = ['companyname'=>$data['companyname'], 'shippingaddress1'=>$data['shippingaddress1'], 'contactname'=>$data['contactname'], 'contactphone'=>$data['contactphone'], 'contactemail'=>$data['contactemail'], 'ponumber' => $data['ponumber'], 'shipaddress' =>$data['shipaddress'], 'shipid' =>$data['shipid'], 'comments' => $data['comments'], 'inemail' => $data['inemail']];
      $data['infor'] = $infor;
      $data['is_logged_in'] = Connection::get()->isLoggedIn();
      $data['subtotal'] = $this->getSubtotal($cart);
      $data['tax'] = $this->getTax(); 
      $data['taxAmount'] = $this->taxAmount($cart);
      $data['total'] = $this->getTotal($cart);
      $data['productprices'] = $this->productPrice($cart);
      $data['productquantity'] = $this->getPricexQty($cart);
      $data['shipst1'] = $this->getShippingSt1($data['shipid']);
      $data['shipst2'] = $this->getShippingSt2($data['shipid']);
      $data['shipst3'] = $this->getShippingSt3($data['shipid']);
      $data['shipcity'] = $this->getShippingCity($data['shipid']);
      $data['shipprov'] = $this->getShippingProvince($data['shipid']);
      $data['shippstl'] = $this->getShippingPostal($data['shipid']);
      $data['billst1'] = $this->getBillingSt1();
      $data['billst2'] = $this->getBillingSt2();
      $data['billst3'] = $this->getBillingSt3();
      $data['billcity'] = $this->getBillingCity();
      $data['billprovince'] = $this->getBillingProvince();
      $data['billpostal'] = $this->getBillingPostal();
      $data['custid'] = Auth::user()->getCustomerID();
      $data['invoice'] = $this->getInvoiceEmails(); 
   
      $this->clearCartFinal();
      return View::make('ProductStore::checkoutComplete', $data);
    }
  }

  
 /*
  * Takes the passed in skuname and queries the database for the price of the product based off the customer pricelist which is their group
  */
  public function getPrice($price){
  
    $group = Auth::user()->getGroup();
    
    $prod = Price::where('PRICELIST', $group)->where('FMTITEMNO', $price)->pluck('DBASEPRICE');
    
    if(!$prod){
      return 'This product needs a quote';
    }
      
    return "$".$prod;
    
    
  }
  /*
  * Similair to previous function but used to calculate the price based off the quantity of the selected item
  */
    public function getPriceQty($price, $qty){
    $group = Auth::user()->getGroup();
    $prod = Price::where('PRICELIST', $group)->where('FMTITEMNO', $price)->pluck('DBASEPRICE');
    
    if(!$prod){
      return 'This product needs a quote';
    }
     
      $prodd= floatval($prod);
      $qtyfloat= floatval($qty);
     
    return $prodd*$qtyfloat;
    
    
  }
  
  private function productPrice($cart)
  {

    $group = Auth::user()->getGroup();
    $productprices=[]; 
    
    foreach ($cart->cartItems as $item){
      
    $prod = Price::where('PRICELIST', $group)->where('FMTITEMNO', $item->skucode)->pluck('DBASEPRICE');

    if(!$prod){
      $productprices[]= 'This product needs a quote';
    }
      $productprices[]=$prod;
    	
    }
    return $productprices;

  }
  
   private function getPricexQty($cart){

    $group = Auth::user()->getGroup();
    $pricearray=[]; 
    
    foreach ($cart->cartItems as $item){
      
    $prod = Price::where('PRICELIST', $group)->where('FMTITEMNO', $item->skucode)->pluck('DBASEPRICE');
    
    if(!$prod){
      $pricearray[]= 'This product needs a quote';
    }
     
      $prodd= floatval($prod);
      $qtyfloat= floatval($item->quantity);
      $pricearray[]= $prodd*$qtyfloat;
      
    }
     
    return $pricearray;
    
    
  }
    
 /*
  * Takes in the current cart object and iterates through all items to calculate the subtotal 
  */
  private function getSubtotal($cart){  
       
    $stotal=0.00;
    foreach ($cart->cartItems as $item)
      $stotal += floatval($this->getPriceQty($item->skucode, $item->quantity));           
    
    
    return $stotal; 
    
  }
  
  private function taxAmount($cart){
    $subtotal = $this->getSubtotal($cart); 
    $rate = floatval($this->getTax()); 
    $taxtotal = $subtotal*($rate/100.00);
    
    return number_format((float)$taxtotal, 2, '.', '');
    
  }
  
  private function getTotal($cart){
    $subtotal = $this->getSubtotal($cart); 
    $taxtotal = $this->taxAmount($cart);
    
    $total = $subtotal + $taxtotal;
    return number_format((float)$total, 2, '.', ''); 
    
  }
  /*
  * Retrieves user customerid and finds the corresponding tax rates. According to tax list each customerid has only one tax rate 
  */
  private function getTax(){
    $custid = Auth::user()->getCustomerID(); 
    
    $taxgrp = CustomerTax::where('IDCUST', $custid)->pluck('CODETAXGRP');
    $taxstate = CustomerTax::where('IDCUST', $custid)->pluck('TAXSTTS1');
    
    $taxamt = TaxRate::where('AUTHORITY', $taxgrp)->where('BUYERCLASS', $taxstate)->pluck('ITEMRATE1');
    
    return $taxamt; 
  }
  
  /*
  * User customerid find the corresponding billing address. Each customerid has one unique billing address 
  */
  private function getBillingAddress(){
    
    $custid = Auth::user()->getCustomerID();
    
       $replace = [
      "/\s*,\s*/" => ",",
      "/,+/"      => ",",
      "/ {2,}/"   => " "
    ];
  
  
    $name =  BillingAddress::where('IDCUST', $custid)->pluck('NAMECUST'); 
    $street1= BillingAddress::where('IDCUST', $custid)->pluck('TEXTSTRE1');  
    $street2= BillingAddress::where('IDCUST', $custid)->pluck('TEXTSTRE2'); 
    $street3= BillingAddress::where('IDCUST', $custid)->pluck('TEXTSTRE3'); 
    $city= BillingAddress::where('IDCUST', $custid)->pluck('NAMECITY'); 
    $prov = BillingAddress::where('IDCUST', $custid)->pluck('CODESTTE'); 
    $postal= BillingAddress::where('IDCUST', $custid)->pluck('CODEPSTL'); 
  
	if((string)$name !=""){
      
    $baddress = (string)$name.','.(string)$street1.','.(string)$street2.','.(string)$street3.','.(string)$city.','.(string)$prov.','.(string)$postal;
      
    }else{
      
      $baddress = (string)$street1.','.(string)$street2.','.(string)$street3.','.(string)$city.','.(string)$prov.','.(string)$postal;
    }
    
     $billaddress = preg_replace( array_keys( $replace ), array_values( $replace ), $baddress );
    
    return $billaddress; 

     
  }
  /*
  * Uses customerid to retrieve all the shipping addresses associated
  */
  private function getShippingAddresses(){

    $custid = Auth::user()->getCustomerID(); 
    $replace = [
      "/\s*,\s*/" => ",",
      "/,+/"      => ",",
      "/ {2,}/"   => " "
    ]; 
    $result = ShipAddress::where('IDCUST', $custid)->select('NAMELOCN','TEXTSTRE1', 'TEXTSTRE2', 'TEXTSTRE3', 'NAMECITY', 'CODESTTE', 'CODEPSTL')->get();
    $addressArray =[];
    
    foreach ($result as $record) {

      if((string)$record->NAMELOCN !=""){
      $address[] = (string)$record->NAMELOCN.','.(string)$record->TEXTSTRE1.','.(string)$record->TEXTSTRE2.','.(string)$record->TEXTSTRE3.','.(string)$record->NAMECITY.','.(string)$record->CODESTTE.','.(string)$record->CODEPSTL;
        $addressArray = preg_replace( array_keys( $replace ), array_values( $replace ), $address );
       
        
      } else{
        $address[] = (string)$record->TEXTSTRE1.','.(string)$record->TEXTSTRE2.','.(string)$record->TEXTSTRE3.','.(string)$record->NAMECITY.','.(string)$record->CODESTTE.','.(string)$record->CODEPSTL;
        $addressArray = preg_replace( array_keys( $replace ), array_values( $replace ), $address );
      }
      
      
    }
    
    return $addressArray; 
  }
  
   private function getLocationID(){

    $custid = Auth::user()->getCustomerID(); 
   
    $result = ShipAddress::where('IDCUST', $custid)->select('id')->get();
    $locationid = []; 
    
    foreach ($result as $record) {
      $locationid[] = $record->id; 
      
    }
    
    return $locationid; 
  }
  
  public function storeTransaction($cart, $data){
    $order = new Order; 
    $user = User::findByID(Connection::get()->getAPISession()->userInfo()['login']); 
    
    $order->cartid = $cart->id; 
    $order->orderConfirmationNumber = substr(sha1($cart->id), 0, 6);
    $order->ponumber = $data['ponumber'];
    $order->shippingaddress = $this->getShippingSt1($data['shipid']);
    $order->shippingtown = $this->getShippingCity($data['shipid']);
    $order->shippingpostal = $this->getShippingPostal($data['shipid']);
    $order->billingaddress = $this->getBillingSt1();
    $order->billingtown = $this->getBillingCity();
    $order->billingpostal = $this->getBillingPostal();
    $order->subtotal = $this->getSubtotal($cart);
    $order->taxamount = $this->taxAmount($cart);
    $order->total = $this->getTotal($cart);
    $order->userid = $user->id; 
    
    $order->save(); 
  
  }
  
 private function getShippingSt1($locationid){ 
   $result = ShipAddress::where('id', $locationid)->pluck('TEXTSTRE1');
    
  return $result; 
 }
  private function getShippingSt2($locationid){ 
    $result = ShipAddress::where('id', $locationid)->pluck('TEXTSTRE2');

    return $result; 
  }

  private function getShippingSt3($locationid){ 
    $result = ShipAddress::where('id', $locationid)->pluck('TEXTSTRE3');

    return $result; 
  }

  private function getShippingCity($locationid){ 
    $result = ShipAddress::where('id', $locationid)->pluck('NAMECITY');

    return $result; 
  }

  private function getShippingProvince($locationid){ 
    $result = ShipAddress::where('id', $locationid)->pluck('CODESTTE');

    return $result; 
  }
  
  private function getShippingPostal($locationid){ 
    $result = ShipAddress::where('id', $locationid)->pluck('CODEPSTL');

    return $result; 
  }


  private function getBillingSt1(){
    $custid = Auth::user()->getCustomerID();
    
    $result = BillingAddress::where('IDCUST', $custid)->pluck('TEXTSTRE1');

    return $result; 
  }

  private function getBillingSt2(){
    
    $custid = Auth::user()->getCustomerID();
    
    $result = BillingAddress::where('IDCUST', $custid)->pluck('TEXTSTRE2');

    return $result; 
  }
  private function getBillingSt3(){
    
    $custid = Auth::user()->getCustomerID();
    
    $result = BillingAddress::where('IDCUST', $custid)->pluck('TEXTSTRE3');

    return $result; 
  }
  private function getBillingCity(){
    
    $custid = Auth::user()->getCustomerID();
    
    $result = BillingAddress::where('IDCUST', $custid)->pluck('NAMECITY');

    return $result; 
  }
  private function getBillingProvince(){
    
    $custid = Auth::user()->getCustomerID();
    
    $result = BillingAddress::where('IDCUST', $custid)->pluck('CODESTTE');

    return $result; 
  }
  private function getBillingPostal(){

    $custid = Auth::user()->getCustomerID();
    
    $result = BillingAddress::where('IDCUST', $custid)->pluck('CODEPSTL');

    return $result; 
  }
  
  private function getInvoiceEmails(){

    $custid = Auth::user()->getCustomerID(); 

    $result = InvoiceEmail::where('IDCUST', $custid)->select('INVOICEEMAIL')->get();
    $emailsArray =[];

    foreach ($result as $record) {
      $emailsArray[] = $record->INVOICEEMAIL; 
    }

    return $emailsArray; 
  }  

}
