<?php

/*
 *
 */

namespace BndProductlist\Controllers;

use BaseController;
use WebpalCore\Controllers\Core as WebPalCore;

class DataController extends BaseController
{
  protected $wpCore;
  public function __construct() {
    $this->wpCore = new WebPalCore();
  }
  public function showNewDataPage() {
    return $this->wpCore->render(array(
      // 'design' => 'standard',
      'xpath' => '//pages/page[@name="products"]/bnd-products/productSection/productCategory/productList/product/description'
    ));
  }
}

