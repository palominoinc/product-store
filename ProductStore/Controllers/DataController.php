<?php

/*
 *
 */

namespace ProductStore\Controllers;

use BaseController;
use \WebpalCore\Controllers\Core as WebpalCore;

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
}
