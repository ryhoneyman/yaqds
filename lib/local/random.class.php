<?php
// Copyright 2023 - Ryan Honeyman

include_once 'common/random.class.php';

class Random extends LWPLib\Random
{
   protected $version = 1.0;

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
 
   public function randFloat(int|float $min, int|float $max, ?int $precision = 5)
   {
      return sprintf("%.".$precision."f",$min + (lcg_value() * ($max - $min)));
   }

   public function randReal(?int $precision = 5)
   {
      return sprintf("%.".$precision."f",lcg_value());
   }
}
