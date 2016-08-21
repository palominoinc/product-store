<?php

/*
 *
 */

namespace ProductStore\Models;
use Eloquent;


class Productstorecart extends Eloquent
{
  protected $table = "productstorecart";
  
  /** 
  * clears the cart
  */
  public function clear() {
    $this->items()->delete();
  }
  
  /** 
  * return item count
  */
  public function itemcount() {
    return $this->items()->count();
  }
  
  /** 
  * return item count
  */
  public function items() {
    return $this->hasMany('Productstorecartitem');
  }
  
  /** 
  * return adds an item given some string details 
  * and returns an item object
  */
  public function addItem(array $itemdetails) {
    $item = Productstorecartitem::create(
      array ( 
        'skucode' => $itemdetails['skucode'],
        'skuname' => $itemdetails['skuname'],
        'productcode' => $itemdetails['productcode'],
        'productname' => $itemdetails['productname'],
        'quantity' => 1,
      ));
    $this->items()->save($item);
    $this->save();
    return $item;
  }
  
  /** 
  * return first item object that matches any of the 
  * itemdetails fields exactly (case-insensitive)
  */
  public function getItem(array $itemdetails) {
    return $this->items()->where('skucode', 'LIKE', $itemdetails['skucode']);
  }
  
  /** 
  * return set quantity for an item object
  */
  public function setQuantity($item, $qty) {
    if ($item) {      
      $item->quantity = $qty;
      $item->save();
      $this->save();
    }
  }
  
  /** 
  * mark this cart as checked out
  */
  public function checkout() {
    $this->checkedout_at = time();
    $this->save();
  }
  

}
