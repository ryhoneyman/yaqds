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

class Input extends Base
{
   protected $version  = 1.0;
   private   $patterns = null;
   public    $postBody = null;

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class.php
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);

      $this->postBody = file_get_contents('php://input');

      $this->patterns = array(
         'alpha'   => 'a-zA-Z',
         'numeric' => '\d',
         'alphanumeric' => '\w',
         'space' => '\ ',
         'dot' => '\.',
         'period' => '\.',
         'comma' => '\,',
         'dash' => '\-',
         'plus' => '\+',
         'pipe' => '\|',
         'underscore' => '\_',
         'colon' => '\:',
         'semicolon' => '\;',
         'parenthesis' => '\(\)',
         'brackets'    => '\[\]',
         'forwardslash' => '\/',
         'backslash' => addslashes('\\'),
         'ampersand' => '\&',
         'percent' => '\%',
         'star' => '\*',
         'equals' => '\=',
         'apostrophe' => "\`",
         'at' => '\@',
         'newline' => addslashes('\\n\\r'),
         'greater' => '\>',
         'less'    => '\<',
         'quote' => '\"',
         'singlequote' => "\'",
         'curlybrackets' => '\{\}',
         'hashtag' => '\#',
      );
   }

   public function strip($value, $regex)
   {
      $return = preg_replace("/$regex/",'',$value);

      return $return;
   }

   public function match($value, $regex)
   {
      $return = preg_match("/$regex/",$value);

      return $return;
   }

   public function isDefined($name)
   {
      return (isset($_GET[$name]) || isset($_POST[$name]));
   }

   public function get($name, $allowed = 'alphanumeric', $default = null, $emptyDefault = false)
   {
      $value = ($emptyDefault) ? $this->variableEmptyDefault($name,$default) : $this->variable($name,$default);

      return $this->validate($value,$allowed);
   }

   public function validate($value, $allowed = 'alphanumeric')
   {
      // If value is a built-in entity, just pass it back - it won't be tainted
      if ($value === null || $value === true || $value === false) { return $value; }

      // This assumes the calling script will perform additional checks against the input
      // since we are returning the raw value here.  Allowing all can be dangerous if not
      // tempered on the caller side.
      //=================================================================================
      if ($allowed == 'all') {
         return $value;
      }
      else if ($allowed == 'email') {
         $emailList   = (is_array($value)) ? $value : preg_split('/[,;]/',preg_replace('/\s/','',$value));
         $validEmails = array_filter(filter_var_array($emailList,FILTER_VALIDATE_EMAIL));

         if (!$validEmails) { return null; }

         // Return an array of emails if we were given an array, otherwise a string of emails
         $return = (is_array($value)) ? $validEmails : implode(',',$validEmails);

         return $return;
      }

      $pattern = '[^';
      foreach (explode(',',$allowed) as $type) {
         $pattern .= ($this->patterns[$type]) ? $this->patterns[$type] : '';
      }
      $pattern .= ']+';

      if (is_array($value)) {
         $return = array();
         foreach ($value as $thisvalue) {
            $return[] = $this->strip($thisvalue,$pattern);
         }
      }
      else {
         $return = $this->strip($value,$pattern);
      }

      return $return;
   }

   public function getFile($name)
   {
      return $_FILES[$name];
   }

   public function variableEmptyDefault($name, $default = null)
   {
      $value = (isset($_GET[$name])) ? $_GET[$name] : ((isset($_POST[$name])) ? $_POST[$name] : $default);

      if ((is_array($value) && empty($value)) || preg_match('/^\s*$/',$value)) { $value = $default; }

      return $value;
   }

   public function variable($name, $default = '')
   {
      return (isset($_GET[$name])) ? $_GET[$name] : ((isset($_POST[$name])) ? $_POST[$name] : $default);
   }
}

?>
