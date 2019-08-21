<?php
/**********************************************************************************************************************************
											WebPal Product Suite Framework Libraries
-----------------------------------------------------------------------------------------------------------------------------------

CronController.php

This file contains all controller logic for the cron jobs in the BND Product Store application.

@author     Ian Stewart <ian@palominosys.com>
@date		July 29th, 2019

(c) 2002-present: all copyrights are with Palomino System Innovations Inc. (Palomino Inc.) of Toronto, Canada

Unauthorized reproduction, licensing or disclosure of this source code will be prosecuted. WebPal is a registered trademark of
Palomino System Innovations Inc. To report misuse please contact info@palominosys.com or call +1 416 964 7333.

**********************************************************************************************************************************/

// Set the namespace and include any necessary classes.
namespace ProductStore\Controllers;

use BaseController;
use \WebpalCore\Controllers\Core as WebpalCore;
use Input;
use \WebpalCore\Source\Services\WebPalResponse;
use Redirect;
use Response;
use \ProductStore\Models\Productstorecart;
use Log;
use Bugsnag;
use Config;
use WebpalCore\Source\DocumentManagerSession;
use \ProductStore\Models\ShipAddress;
use \ProductStore\Models\BillingAddress;
use \ProductStore\Models\Price;
use \ProductStore\Models\CustomerTax;
use \ProductStore\Models\TaxRate;
use Illuminate\Support\Facades\DB;

// This controller contains the logic for all cron jobs in the BND Product Store application.
class CronController extends BaseController
{
  // The token required to run the cron jobs.
  private $cron_token = '90u80JKNUjiojiojf09839r0jrq230U90ua90sjdaij09u';
  
  // The folder housing the import files.
  private $import_directory = '/Imports';
  
  // The lists to import.
  private $lists_to_import = [
    'Prices', 'ShippingAddresses', 'BillingAddresses', 'CustomerTax', 'TaxRates'
  ];
  
  // If true, files are moved into an archive folder after processing.
  private $archive_files = false;
  

  /*
   * Function name: importLists
   * 
   * This function checks if any new lists have been uploaded to the Document Manager, and if so, replaces the existing tables with the data found in the new CSVs.
   * 
   * Usage:
   *  *  *  *  *  *  /usr/bin/wget --no-check-certificate -q -O - "https://bndinc.com/product-store/cron/import_lists/90u80JKNUjiojiojf09839r0jrq230U90ua90sjdaij09u" > /dev/null
   * 
   * Input:
   * - token: a secure auth token used to prevent users from hitting the cron URL.
   * 
   * Returns: a JSON object indicating whether the import was successful.
   */
  public function importLists($token)
  {
    $message = '';
    
    // Make sure that the proper token was provided in the URL.
    if ($token === $this->cron_token)
    {
      $result = true;
      
      // Attempt to import each list type.
      foreach ($this->lists_to_import as $list_to_import)
      {
        $import_function = "import{$list_to_import}";
        $import_result = $this->$import_function();
   
        // If an error occurred during import, return an error.
        if (!$import_result)
        {
         
          $message .= ((!empty($message)) ? ' ' : '') . "Error importing {$list_to_import}.";
          $result = false;
        }
      }
    }
    // Invalid token provided.
    else
    {
      $result = false;
      $message = 'Invalid token.';
    }
    
    // If no errors occurred, return true.
    if ($result)
    {
      $response = [
        'success' => true,
        'message' => 'Import successful.',
            
      ];
       
    }
    // Otherwise, return false with an error message.
    else
    {
      $response = [
        'success' => false,
        'message' => $message
      ];
    }
    
    return Response::json($response);
  }
  
  /*
   * Function name: getUploadedList
   * 
   * This function checks if the specified list file exists in the Document Manager. If so, it returns the CSV parsed into an array. If specified, it can also move the file into archive.
   * 
   * Input:
   * - list_name: the name of the folder to check in the Document Manager.
   * 
   * Returns: if a list file is found, an array with the CSV data; otherwise false.
   */
  private function getUploadedList($list_name)
  {
    // Check the specified directory for a new list.
    $dm_session = $this->getDMSession();
    $directory = "{$this->import_directory}/{$list_name}";
    $directory_contents = $dm_session->listDirectory($directory);
    $uploaded_list = false;
    
    if (!empty($directory_contents)){
      
    foreach ($directory_contents as $child)
    {
      // Check if any CSVs exist.
      if (stripos($child['name'], '.csv') !== false)
      {
        // Download the file and parse into an array.
        $file_contents = $dm_session->download($child['prettyName']);
        $rows = explode(PHP_EOL, $file_contents);
		$uploaded_list = [];
        
		foreach ($rows as $row)
        {
    	  $uploaded_list[] = str_getcsv($row);
		}
        
        // If specified, move the processed file to an archive folder.
        if ($this->archive_files)
        {
          $archive_name = str_replace('.csv', '_' . date('YmdHis') . '.csv', $child['name']);
          $archive_path = "{$this->import_directory}/{$list_name}/{$archive_name}";
          $dm_session->rename($child['prettyName'], $archive_name);
          $dm_session->move($archive_path, "{$this->import_directory}/Archive");
        }
        
        break;
      }
    }
  }
    
    return $uploaded_list;
  }
  
  /*
   * Function name: getDMSession
   * 
   * This function checks if a Document Manager session exist. If so, it returns the session. If not, it creates one.
   * 
   * Returns: a Document Manager session.
   */
 private function getDMSession()
  {
    // If no Document Manager session exists, create one.
    if ($this->dm_session === null)
    {
      // Login as the cron job user specified in config.
      $serviceURL = Config::get('app.webpal.url', 'https://bnd.webpal.net');
      $this->dm_session = new DocumentManagerSession("{$serviceURL}/webservice/xml-service.php");
	  $this->dm_session->setVerifyPeer(Config::get('app.webpal.verifyPeer', false));
      
      $user_name = Config::get('app.webpal.cron.login');
      $password = Config::get('app.webpal.cron.password');
      $this->dm_session->login($user_name, $password);
      
      // This call exists because the first call to the DM was failing for some reason, and then all subsequent calls worked. Ideally, this can be removed.
      $import_directory_exists = $this->dm_session->exists($this->import_directory);
    }

    return $this->dm_session;
  }
  
  /*
   * Function name: importPrices
   * 
   * This function checks to see if a new price list exists. If so, it replaces the pricing table accordingly.
   * 
   * Returns: If a list is found and imported correctly, or no new list is found, true. If an error occurs during update, then false.
   */
  private function importPrices()
  {
    $result = true;
    
    // Get the new pricing list, if it exists.
    $uploaded_list = $this->getUploadedList('Prices');
    
    // If a new pricing list exists, replace the pricing list in the database.
    if (!empty($uploaded_list))
    {
      Price::truncate();
      
      $pricelist = $uploaded_list[0][2];
      $dbaseprice = $uploaded_list[0][3];
      $itemno = $uploaded_list[0][5];
      $db = DB::connection()->getPdo();
      $stmt = $db->prepare("INSERT INTO `Price` ($pricelist, $dbaseprice, $itemno) VALUES (?,?,?)");

      for($i = 1; $i < count($uploaded_list); $i++){
   
        $list = $uploaded_list[$i][2];
        $price = $uploaded_list[$i][3];
        $item = $uploaded_list[$i][5];
        
        $data= [$list, $price, $item];
        $stmt->execute($data);		
      }
 
    }
    
    return $result;
  }
  
   /*
   * Function name: importShippingAddresses
   * 
   * This function checks to see if a new shipping list exists. If so, it replaces the old list accordingly.
   * 
   * Returns: If a list is found and imported correctly, or no new list is found, true. If an error occurs during update, then false.
   * Street values and city values were parsed for " ' " using str_replace because it would cause an error when importing into the database
   */
    private function importShippingAddresses()
  {
    $result = true;
    
    // Get the new shipping list, if it exists.
    $uploaded_list = $this->getUploadedList('ShippingAddresses');
    
    // If a new shipping list exists, replace the old shipping list in the database.
    if (!empty($uploaded_list))
    {
      ShipAddress::truncate();
      $custid = $uploaded_list[0][0];
      $location = $uploaded_list[0][2];
      $street1 = $uploaded_list[0][3];
      $street2 = $uploaded_list[0][4];
      $street3 = $uploaded_list[0][5];
      $city = $uploaded_list[0][6];
      $prov = $uploaded_list[0][7];
      $postal = $uploaded_list[0][8];
      $plistid = $uploaded_list[0][15];
      
      $db = DB::connection()->getPdo();
      $stmt = $db->prepare("INSERT INTO `ShipAddress` ($custid, $location, $street1, $street2, $street3, $city, $prov, $postal, $plistid) VALUES (?,?,?,?,?,?,?,?,?)");

      for($i = 1; $i < count($uploaded_list); $i++){
     
        $cust = $uploaded_list[$i][0];
        $loc = $uploaded_list[$i][2];
        $st1 = $uploaded_list[$i][3];
        $st2 = $uploaded_list[$i][4];
        $st3 = $uploaded_list[$i][5];
        $custcity = $uploaded_list[$i][6];
        $pro = $uploaded_list[$i][7];
        $custpostal = $uploaded_list[$i][8];
        $priceid = $uploaded_list[$i][15];
        
        $data= [$cust, $loc, $st1, $st2, $st3, $custcity, $pro, $custpostal, $priceid];
        $stmt->execute($data);
  
      }
    }
    
    return $result;
  }
  
  /*
   * Function name: importBillingAddresses
   * 
   * This function checks to see if a new billing list exists. If so, it replaces the old list accordingly.
   * 
   * Returns: If a list is found and imported correctly, or no new list is found, true. If an error occurs during update, then false.
   * Street values and city values were parsed for " ' " using str_replace because it would cause an error when importing into the database
   */
    private function importBillingAddresses()
  {
    $result = true;
    
    // Get the new billing list, if it exists.
    $uploaded_list = $this->getUploadedList('BillingAddresses');
      
    // If a new billing address list exists, replace the pricing list in the database.
    if (!empty($uploaded_list))
    {
       BillingAddress::truncate();
      
      $custid = $uploaded_list[0][0];
      $name = $uploaded_list[0][1];
      $street1 = $uploaded_list[0][2];
      $street2 = $uploaded_list[0][3];
      $street3 = $uploaded_list[0][4];
      $city = $uploaded_list[0][6];
      $prov = $uploaded_list[0][7];
      $postal = $uploaded_list[0][8];
      
      $db = DB::connection()->getPdo();
      $stmt = $db->prepare("INSERT INTO `BillingAddress` ($custid, $name, $street1, $street2, $street3, $city, $prov, $postal) VALUES (?,?,?,?,?,?,?,?)");
      
      for($i = 1; $i < count($uploaded_list); $i++){
        
        $cust = $uploaded_list[$i][0];
        $customer = $uploaded_list[$i][1];
        $st1 = $uploaded_list[$i][2];
        $st2 = $uploaded_list[$i][3];
        $st3 = $uploaded_list[$i][4];
        $custcity = $uploaded_list[$i][6]; 
        $custprov = $uploaded_list[$i][7];
        $custpostal = $uploaded_list[$i][8];
        
        $data= [$cust, $customer, $st1, $st2, $st3, $custcity, $custprov, $custpostal];
         $stmt->execute($data);
           
    }
  }
    
    return $result;
  }
  
  /*
   * Function name: importCustomerTax
   * 
   * This function checks to see if a new customerTax list exists. If so, it replaces the old list accordingly.
   * 
   * Returns: If a list is found and imported correctly, or no new list is found, true. If an error occurs during update, then false.
   */
    private function importCustomerTax()
  {
    $result = true;
    
    // Get the new pricing list, if it exists.
    $uploaded_list = $this->getUploadedList('CustomerTax');
    
    // If a new customer tax list exists, replace the list with the new list in the database.
    if (!empty($uploaded_list))
    {
      CustomerTax::truncate();

      $id = $uploaded_list[0][0];
      $tax = $uploaded_list[0][1];
      $state = $uploaded_list[0][2];
	  $db = DB::connection()->getPdo();
      $stmt = $db->prepare("INSERT INTO `CustomerTax` ($id, $tax, $state) VALUES (?,?,?)");
      
      for($i = 1; $i < count($uploaded_list); $i++){
     
        $custid = $uploaded_list[$i][0];
        $taxgroup = $uploaded_list[$i][1];
        $groupstate = $uploaded_list[$i][2];
        
  		$data= [$custid, $taxgroup, $groupstate];
        $stmt->execute($data);	
      }
    }
    
    return $result;
  }
  
  /*
   * Function name: importTaxRates
   * 
   * This function checks to see if a new shipping list exists. If so, it replaces the old list accordingly.
   * 
   * Returns: If a list is found and imported correctly, or no new list is found, true. If an error occurs during update, then false.
   */
      private function importTaxRates()
  {
    $result = true;
    
    // Get the new tax rate list, if it exists.
    $uploaded_list = $this->getUploadedList('TaxRates');
    
    // If a new tax rate list exists, replace the list in the database with the new one.
    if (!empty($uploaded_list))
    {
      TaxRate::truncate();

      $authority = $uploaded_list[0][0];
      $class = $uploaded_list[0][1];
      $itemrate = $uploaded_list[0][2];
      
      $db = DB::connection()->getPdo();
      $stmt = $db->prepare("INSERT INTO `TaxRate` ($authority, $class, $itemrate) VALUES (?,?,?)");

      for($i = 1; $i < count($uploaded_list); $i++){
        
        $taxtype = $uploaded_list[$i][0];
        $taxgroup = $uploaded_list[$i][1];
        $percentage = $uploaded_list[$i][2];
        
        $data= [$taxtype, $taxgroup, $percentage];
        $stmt->execute($data);	
  
      }
    }
    
    return $result;
  }
}