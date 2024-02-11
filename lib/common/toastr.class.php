<?php

//    Copyright 2009,2010 - Ryan Honeyman
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>
//


//======================================================================================================
// Overview:
//======================================================================================================
/* Example:


*/
//======================================================================================================

include_once 'base.class.php';

class Toastr extends Base
{
   protected $version = 1.0;
   public    $options = null;

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);

      $this->options = array(
         //"closeButton" => false,
         "debug" => false,
         "newestOnTop" => true,
         //"progressBar" => true,
         "positionClass" => "toast-top-right",
         "preventDuplicates" => false,
         //"onclick" => null,
         //"showDuration" => "300",
         //"hideDuration" => "1000",
         "timeOut" => "3000",
         //"timeOut" => "0",
         //"extendedTimeOut" => "1000",
         "showEasing" => "swing",
         "hideEasing" => "linear",
         "showMethod" => "fadeIn",
         "hideMethod" => "fadeOut"
      );
   }

   public function success($message)
   {
      return $this->display('success',$message);
   }

   public function failure($message)
   {
      return $this->display('error',$message);
   }

   public function display($type, $message)
   {
      $display = "<script language='javascript' type='application/javascript'>\ntoastr.options = ".json_encode($this->options,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).
                 "\ntoastr['$type']('$message');\n</script>";

      print $display; 

      return $display;
   }

   public function option($name, $value = null, $clear = false)
   {
      if (!is_null($value) || $clear) { $this->options[$name] = $value; }
 
      return $this->options[$name]; 
   }
}

?>
