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
// Overview: Base class from which all other classes are derived
//======================================================================================================
/* Example:

   class Example extends Base
   {
      ...
   }
*/
//======================================================================================================


class Base
{
   protected $version   = 1.0;
   protected $debug     = null;
   protected $options   = array();
   protected $vars      = array();
   protected $errors    = array();

   //===================================================================================================
   // Description: Returns version of this class.
   // Input: null()
   // Output: int(version), Version number
   //===================================================================================================
   public function version() { return $this->version; }

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class.php
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      $this->debug = $debug;

      if (isset($options)) { $this->options = $options; }
   }

   //===================================================================================================
   // Description: Gets option from class if any were set when initialized
   // Input: string(key), Name of option key to retrieve
   // Input: mixed(value), Name of value to set [optional]
   // Input: boolean(clear), Treat null value as valid to clear [optional]
   // Output: string(value), Value of option
   //===================================================================================================
   public function option($key, $value = null, $clear = false)
   {
      if (!is_null($value) || $clear) { $this->options[$key] = $value; }

      return (isset($this->options[$key]) ? $this->options[$key] : null);
   }

   public function var($name, $value = null, $clear = null)
   {
      if (!is_null($value) || $clear) { $this->vars[$name] = $value; }

      return (isset($this->vars[$name]) ? $this->vars[$name] : null);
   }

   //===================================================================================================
   // Description: Debug relay function; available if class was constructed with Debug object.
   // Input: int(level), Level of debug assertion (0-9)
   // Input: string(message), Message to write to debug
   // Output: null()
   //===================================================================================================
   public function debug($level, $message)
   {
      if (!isset($this->debug)) { return; }

      $tracelevel = isset($this->options["tracelevel"]) ? $this->options["tracelevel"] : 2;

      $this->debug->trace($level,$message,$tracelevel);
   }

   //===================================================================================================
   // Description: Checks whether an array is associative
   // Input: array(array), Name of array to check
   // Output: boolean(value), True if array was associative
   //===================================================================================================
   public function isAssoc($array) {
       return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
   }

   public function isJson($string) {
      return (!empty($string) && is_string($string) && is_array(json_decode($string,true)) && json_last_error() == 0);
   }

   public function error($errorMessage = null)
   {
      if (!is_null($errorMessage)) { $this->errors[] = $errorMessage; }
      else {
         $errors = implode('; ',$this->errors);
         $this->errors = array();

         return $errors;
      }
   }
}

?>
