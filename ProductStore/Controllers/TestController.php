<?php

/*
 *
 */

namespace ProductStore\Controllers;

use BaseController;
use \WebpalCore\Controllers\Core as WebpalCore;
use Input;
use \WebpalCore\Source\Services\WebPalResponse;

class TestController extends BaseController
{
  protected $wp;
  public function __construct() {
    $this->wp = new WebpalCore;
  }
  public function showNewDataPage() {
    return $this->wp->render([
      'xpath' => "//pages/page[@name='products']/product-store-2/categories/category[@id='catepVQBVhh2XZCq']",
      'design' => '3-column',
      'cache' => false
    ]);
    
  }
  
  /*
  * queries a store by certain criteria and renders results as a view
  */
  public function search($storeid) {
    // storeid - the id of the product-store node to query
    // q - the query from the request, used to perform a partial match on a product's title
    
    
    
    $storeid = 'wpPrNyFKIOawa9a1';
    $cond = "contains(title, \"laptop\")";
    
    $xquery = "//product-store[@id='$storeid']//product[1]";
    
    $params = [];
    //$params['mode'] = 'search';
    //return $xquery;
    return WebPalResponse::view($xquery, array(), 200, array(), $params);
    
    $q = Input::get('q');
    //$cond = "contains(title, '$q')";
    $cond = "contains(title, 'gloves')";
    // $cond = "1";
    $xquery = "//product-store[@id='$storeid']/products/product[$cond]";
    $params['mode'] = 'search';
    return WebPalResponse::view($xquery, Input::all(), 200, array(), $params);
    
  }
}
