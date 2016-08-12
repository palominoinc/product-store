<?php

/*
 *
 */

namespace ProductStore\Controllers;

use BaseController;
use \WebpalCore\Controllers\Core as WebpalCore;
use Input;
use \WebpalCore\Source\Services\WebPalResponse;

class DataController extends BaseController
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
    $q = Input::get('q');
    $cond = "contains(title, '$q')";
    // $cond = "1";
    $xquery = "//product-store[@id='$storeid']/products/product[$cond]";
    $params['mode'] = 'search';
    return WebPalResponse::view($xquery, Input::all(), 200, array(), $params);
    
  }
}
