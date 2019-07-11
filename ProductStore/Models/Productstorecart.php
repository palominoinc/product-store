<?php

/*
 *
 */

namespace ProductStore\Models;
use Eloquent;
use Productstorecartitem;


class Productstorecart extends Eloquent
{
  protected $table = "productstorecart";
  protected $guarded = array('id', 'ipaddress', 'checkedout_at', 'created_at');

  /** 
  * clears the cart
  */
  public function clear() {
    $this->cartItems()->delete();
  }
  
  /** 
  * return item count
  */
  public function itemcount() {
    return $this->cartItems()->count();
  }
  
  /** 
  * return item count
  */
  public function cartItems() {
    return $this->hasMany('\\ProductStore\\Models\\Productstorecartitem');
  }
  
  /** 
  * return adds an item given some string details 
  * and returns an item object
  */
  public function addItem(array $itemdetails) {
    $item = new \ProductStore\Models\Productstorecartitem;
    $item->skucode = $itemdetails['skucode'];
    $item->skuname = $itemdetails['skuname'];
    $item->productcode = $itemdetails['productcode'];
    $item->productname = 
      empty($itemdetails['skudescription'])? 
      $itemdetails['productname'] : $itemdetails['skudescription'];
    $item->quantity = 1;
    $this->cartItems()->save($item);
    $this->save();
    return $item;
  }
  
  /** 
  * return first item object that matches any of the 
  * itemdetails fields exactly (case-insensitive)
  */
  public function getItem(array $itemdetails) {
    //return null;
    return $this->cartItems()->where('skucode', 'LIKE', $itemdetails['skucode'])->first();
  }
  
  /** 
  * return set quantity for an item object
  */
  public function setItemQuantity($item, $qty) {
    if ($item != null) {      
      $item->quantity = $qty;
      $item->save();
      $this->save();
    }
  }
  
  /** 
  * mark this cart as checked out
  */
  public function checkout(array $info=[]) {
    $this->update($info);
    $this->checkedout_at = time();
    // $this->clear();
    $this->save();
  }
}
