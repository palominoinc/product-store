<?php

/*
 *
 */

namespace ProductStore\Models;


class Productstorecart extends Eloquent
{
  /** 
  * clears the cart
  */
  public function clear() {
    
  }
  
  /** 
  * return item count
  */
  public function itemcount() {
    
  }
  
  /** 
  * return adds an item given some string details 
  * and returns an item object
  */
  public function addItem(array $itemdetails) {
    
  }
  
  /** 
  * return first item object that matches any of the 
  * itemdetails fields exactly (case-insensitive)
  */
  public function getItem(array $itemdetails) {
    
  }
  
  /** 
  * return set quantity for an item object
  */
  public function setQuantity($item, $qty) {
    
  }
  
  /** 
  * mark this cart as checked out
  */
  public function checkout() {
    
  }
  

}
