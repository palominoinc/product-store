<?php

/*
 *
 */

namespace ProductStore\Models;
use Eloquent; 
use \ProductStore\Models\User;

class Order extends Eloquent 
{
  protected $table = "order";
  
  public function getUser()
  {
     return $this->belongsTo('\\ProductStore\\Models\\User');  
  }
}

