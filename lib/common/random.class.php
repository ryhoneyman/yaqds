<?php

//======================================================================================================
// Overview:
//======================================================================================================
/* Example:


*/
//======================================================================================================

include_once 'base.class.php';

class Random extends Base
{
   protected $version  = 1.0;

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class.php
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);
   }

   public function generatePassword($length, $include = null, $exclude = null, $options = null)
   {
      if ($length < 8) { $this->error('minimum password length is 8 characters'); return false; }

      if (is_null($include)) { $include = 'alphanum,numbers/1,symbols/1'; }

      $includeList = array(
         'symbol' => '`~!@#$%^&*()-_=+[{]}\|;:\'",<.>/?',
         'number' => '0123456789',
         'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
         'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
      );

      $includeList['alpha']    = $includeList['lowercase'].$includeList['uppercase'];
      $includeList['alphanum'] = $includeList['lowercase'].$includeList['uppercase'].$includeList['number'];

      $excludeList = array(
         'similar'   => '\{\}\[\]\(\)\/\\\\\'\"\`\~\,\.\;\:\<\>',
         'ambiguous' => 'iI1loO0\|',
      );

      $pattern = $this->generatePasswordPattern($length,$include);

      if ($pattern === false) { return false; }

      $excludeSpace = '';

      if (!is_null($exclude)) {
         foreach (explode(',',$exclude) as $excludeType) {
            $excludeChars = $excludeList[$excludeType];

            if (!$excludeChars) { continue; }

            $excludeSpace .= $excludeChars;
         }
      }

      $keySpace = array();
      $password = '';

      foreach ($includeList as $includeType => $includeSpace) {
         $keySpace[$includeType]['characters'] = ($excludeSpace) ? preg_replace("|[$excludeSpace]|",'',$includeSpace) : $includeSpace;
         $keySpace[$includeType]['count'] = strlen($keySpace[$includeType]['characters']) - 1;
      }

      foreach ($pattern as $includeType) {
         $password .= $keySpace[$includeType]['characters'][random_int(0,$keySpace[$includeType]['count'])];
      }

      return $password;
   }

   public function generatePasswordPattern($length, $pattern, $shuffle = false)
   {
      if (!$pattern) { $this->error('no pattern provided'); return false; }

      // Provided pattern is comma separated list of types, and optional /counts appended
      // If the first element doesn't have a count, we'll assume that's the base type
      $patternList = explode(',',$pattern);
      $basePattern = (preg_match('~/~',reset($patternList))) ? null : array_shift($patternList);

      $return = array();

      foreach ($patternList as $entry) {
         list($entryType,$entryCount) = explode('/',$entry);

         if ($entryCount < 1) { $entryCount = 1; }

         $return = array_merge($return,array_fill(0,$entryCount,$entryType));
      }

      // If we had a base pattern, fill the rest of the spots with the base type
      // Force shuffle if we are filling with base type to avoid run of the same type at the end
      if (!is_null($basePattern)) {
         $return  = array_merge($return,array_fill(0,$length - count($return),$basePattern));
         $shuffle = true;
      }

      if (count($return) != $length) { $this->error('pattern provided did not match requested length'); return false; }

      if ($shuffle) { shuffle($return); }

      return $return;
   }

   public function generateInteger($minInt, $maxInt, $assignedInts = null)
   {
      $return   = array();
      $intTotal = $maxInt - $minInt;

      if (is_null($assignedInts)) { $assignedInts = array(); }

      if ($intTotal < 1) { $this->error('invalid min and max values specified'); return false; }

      // Available space has run out
      if (count($assignedInts) == $intTotal) { $this->error('no free integers available'); return false; }

      $intSpace = array_diff(range($minInt,$maxInt),$assignedInts);

      shuffle($intSpace);

      $newInt = array_shift($intSpace);

      return $newInt;
   }

   public function uniqueId($length = 13)
   {
      $bytes = random_bytes(ceil($length/2));

      return substr(bin2hex($bytes),0,$length);
   }
}

?>
