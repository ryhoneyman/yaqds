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
include_once 'evalengine.class.php';

class Arrays extends Base
{
   protected $version  = 1.0;
   public    $value    = null;
   private   $ee       = null;

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class.php
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);

      $this->ee = new EvalEngine($debug);
   }

   public function arrayDepth($array)
   { 
      if (!is_array($array)) return 0;
      if (empty($array))     return 1;

      return max(array_map(array($this,__FUNCTION__),$array))+1;
   }

   public function kSortRecursive(&$array)
   {
      $this->debug(8,"called");

      foreach ($array as &$value) {
         if (is_array($value)) { $this->kSortRecursive($value); }
      }

      return ksort($array);
   }

   public function diffKeyRecursive($array1, $array2)
   {
      $this->debug(8,"called");

       $outputDiff = array();

       foreach ($array1 as $key => $value) {
           if (array_key_exists($key,$array2)) {
               if (is_array($value)) {
                   $recursiveDiff = $this->diffKeyRecursive($value,$array2[$key]);

                   if (count($recursiveDiff)) { $outputDiff[$key] = $recursiveDiff; }
               }
           }
           else { $outputDiff[$key] = $value; }
       }

       return $outputDiff;
   }

   public function diffAssocRecursive($array1, $array2)
   {
      $this->debug(8,"called");

      $outputDiff = array();

      foreach ($array1 as $key => $value) {
         if (is_array($value)) {
            if (!isset($array2[$key]) || !is_array($array2[$key])) { $outputDiff[$key] = $value; }
            else {
               $recursiveDiff = $this->diffAssocRecursive($value,$array2[$key]);
               if ($recursiveDiff !== false) { $outputDiff[$key] = $recursiveDiff; }
            }
         }
         else if (!isset($array2[$key]) || $array2[$key] !== $value) { $outputDiff[$key] = $value; }
     }

     return $outputDiff;
   }

   public function diffRecursive($array1, $array2)
   {
      $this->debug(8,"called");

       $outputDiff = array();

       foreach ($array1 as $key => $value) {
           //if the key exists in the second array, recursively call this function
           //if it is an array, otherwise check if the value is in arr2
           if (array_key_exists($key,$array2)) {
               if (is_array($value)) {
                   $recursiveDiff = $this->diffRecursive($value,$array2[$key]);

                   if (count($recursiveDiff)) { $outputDiff[$key] = $recursiveDiff; }
               }
               else if (!in_array($value,$array2)) { $outputDiff[$key] = $value; }
           }
           //if the key is not in the second array, check if the value is in
           //the second array (this is a quirk of how array_diff works)
           else if (!in_array($value,$array2)) { $outputDiff[$key] = $value; }
       }

       return $outputDiff;
   }

   public function intersectKeyRecursive($array1, $array2, $initial = true)
   {
      $this->debug(8,"called");

      $this->value = null;

      $array1 = array_intersect_key($array1,$array2);

      foreach ($array1 as $key => &$value) {
         if ($array2[$key] === true) { $this->value = $value; }
         else if (is_array($value)) { $value = is_array($array2[$key]) ? $this->intersectKeyRecursive($value,$array2[$key],false) : $value; }
      }

      if (!is_null($this->value)) { return $this->value; }

      return $array1;
   }

   public function advancedMatch($needles, $haystack)
   {
      $this->debug(8,"called");

      $flatHaystack = $this->flatten($haystack);
      $flatMatches  = array();

      foreach ($needles as $needlePath => $needleValues) {
         foreach ($flatHaystack as $haystackPath => $haystackValue) {
            //$this->debug(9,"Match? $haystackPath = $needlePath");
            if (preg_match("~$needlePath~i",$haystackPath)) {
               //$this->debug(9,"Matched!");
               foreach (preg_split('/;/',$needleValues) as $needleValue) {
                  if ($this->ee->evaluate(array('eval' => 'value '.$needleValue),array('value' => $haystackValue))) {
                     $flatMatches[$haystackPath] = $haystackValue;
                  }
               }
            }
         }
      }

      return $this->unflatten($flatMatches);
   }

   public function advancedCast($data, $filter)
   {
      $this->debug(8,"called");

      $flatData = $this->flatten($data);

      foreach ($filter as $filterPath => $filterCast) {
         foreach ($flatData as $dataPath => $dataValue) {
            if (preg_match("~$filterPath~i",$dataPath)) {
               if (preg_match('/^int$/',$filterCast) && !is_int($dataValue)) {
                  $flatData[$dataPath] = (int) $dataValue;
               }
            }
         }
      }

      $finalData = $this->unflatten($flatData);

      $this->debug(8,json_encode(array('finalData' => $finalData)));

      return $finalData;
   }

   public function advancedFilter($data, $filter)
   {
      $this->debug(8,"called");

      $flatData = $this->flatten($data);

      foreach ($filter as $filterPath => $filterActions) {
         foreach ($flatData as $dataPath => $dataValue) {
            if (preg_match("~$filterPath~i",$dataPath)) {
               list($updateValue,$remove) = $this->filterValue($dataPath,$dataValue,$filterActions);

               if ($remove) {
                  $this->debug(8,"removing $dataPath");
                  unset($flatData[$dataPath]);
               }

               if ($dataValue !== $updateValue) {
                  $this->debug(8,"updating ".json_encode(array('dataPath' => $dataPath, 'updateValue' => $updateValue)));
                  $flatData[$dataPath] = $updateValue;
               }
            }
         }
      }

      $finalData = $this->unflatten($flatData);

      $this->debug(8,json_encode(array('finalData' => $finalData)));

      return $finalData;
   }

   public function filterValue($path, $value, $actions)
   {
      $remove      = false;
      $updateValue = $value;

      foreach (preg_split('/[,;\ ]/',$actions) as $action) {
         if      (preg_match('/^removeiffalse$/i',$action) && $value === false) { $remove = true; }
         else if (preg_match('/^removeifnull$/i',$action) && is_null($value)) { $remove = true; }
         else if (preg_match('/^removeifpresent$/i',$action)) { $remove = true; }
         else if (preg_match('/^removeifempty$/i',$action) && (preg_match('/^\s*$/',$value) || empty($value))) { $remove = true; }
         else if (preg_match('/^castto(\S+?)(?:as(string))?$/i',$action,$match)) {
            $castTo = strtolower($match[1]);
            $castAs = ($match[2]) ? strtolower($match[2]) : 'default';

            $this->debug(9,"castTo:$castTo, castAs:$castAs");

            $castList = array(
               'integer' => array(
                  'default' => array('true' => 1, 'false' => 0),
                  'string'  => array('true' => '1', 'false' => '0'),
               ),
               'boolean' => array(
                  'default' => array('true' => true, 'false' => false),
                  'string'  => array('true' => 'true', 'false' => 'false'),
               ),
            );

            $castValue   = (preg_match('/^(1|true)$/',$value) || $value === true) ? 'true' : 'false';
            $updateValue = $castList[$castTo][$castAs][$castValue];
         }
      }

      $this->debug(8,json_encode(array('path' => $path, 'action' => $action, 'value' => $value, 'updateValue' => $updateValue, 'remove' => $remove)));

      return array($updateValue,$remove);
   }

   public function flatten($entry, $prefix = '')
   {
      $flatEntry = array();

      //$this->debug(9,"called: ".json_encode(array('entry' => $entry, 'prefix' => $prefix)));

      foreach ($entry as $key => $value)
      {
         //$this->debug(9,json_encode(array('key' => $key, 'value' => $value)));

         $newKey = $prefix.(empty($prefix) ? '' : '/').$key;

         if (is_array($value)) { $flatEntry = array_merge($flatEntry,$this->flatten($value,$newKey)); }
         else { $flatEntry[$newKey] = $value; }
      }

      // If an empty array entry was given, we need to set an empty array on return
      if (is_array($entry) && empty($entry)) { $flatEntry[$prefix] = array(); }

      //$this->debug(9,"return: ".json_encode(array('flatEntry' => $flatEntry)));

      return $flatEntry;
   }

   public function unflatten($flatEntry)
   {
      $entry = array();

      foreach ($flatEntry as $flatPath => $value) {
         $pathParts = explode('/',$flatPath);
         $current   = &$entry;

         foreach ($pathParts as $key) { $current = &$current[$key]; }

         $current = $value;
      }

      return $entry;
   }
}

?>
