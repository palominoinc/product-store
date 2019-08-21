<?php

/*
 *
 */

namespace ProductStore\Models;

use Eloquent;
use WebpalLogin\Source\WebPalAPI\Connection;
use WebpalLogin\Models\MembershipInfo;
use \ProductStore\Models\User;
use Log;
use Auth;
use Hash;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;
use \ProductStore\Models\Order;



class User extends Eloquent implements UserInterface, RemindableInterface 
{
  use UserTrait, RemindableTrait;
  
  protected $table = 'user';
  protected $hidden = [
    'password'
  ];
  protected $fillable = ['customerid', 'firstName', 'lastName', 'password'];
  public $timestamps = false;

  /*CustomerID will be set as the login for each customer. This function retrieves    *that customerID value which then can be used in DB queries
  */
  
 public function getCustomerID()
 {
   $customerid = Connection::get()->getAPISession()->userInfo()['login'];
   return $customerid; 
 }

  /*Clients will only have one group associated with them. This function gets the      *list of all groups that the webpal customer is in
   *and then returns the value without the key. Should always 
   *return only one group.
   */
  public function getGroup()
  {
	$group_array = Connection::get()->getAPISession()
                   ->userInfo(null, ['user_id' => $user_id])['groupList'];
    
    if (!is_null($group_array)) {
      $pretty = [];
      foreach ($group_array as $key => $group) {
        if (!is_null($group['name'])) {
          $pretty[$key] = $group['name'];
        }
      }
      return $pretty[0];
    }
    return [];
  }
  
  public function findByID($id)
  {
    return User::where('customerid', $id)->first();
  }
  
  public function login($password, $redirect_url, $logout_url)
  {
    $connection = Connection::get();
    $result = false;
    
    if ($this->password === md5($password))
    {
      $this->password = Hash::make($password);
      $this->save();
    }
    
    $result = Auth::loginUsingId($this->id);

    if ($result)
    {
      $result = true;
    }

    return $result; 
  }
  
  public function logout()
  {
    return Auth::logout();
  }

  public function orders()
  {
    return $this->hasMany('\\ProductStore\\Models\\Order');
  }
}



  
