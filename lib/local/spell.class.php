<?php

//    Copyright 2023 - Ryan Honeyman

include_once 'common/base.class.php';

class Spell extends Base
{
   protected $version  = 1.0;
   protected $data     = array();

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

   public function ($spellData)

   public function load($spellData) { $this->data = $spellData; }

   public function calculateEffectValueBySlot($slotNumber, $casterLevel, $ticsRemaining = null)
   {
      $result     = 0;
      $updownSign = 1;
      $slotInfo   = $this->getSlotInfo($slotNumber);
      $base       = $slotInfo['base'];
      $formula    = $slotInfo['formula'];
      $max        = $slotInfo['max'];
      $uBase      = $base;
      
      if ($uBase < 0) { $uBase = -($uBase);

      if ($max < $base && $max != 0) { $updownSign = -1; }

      switch ($formula) {
         case 0:
         case 100: $result = $uBase; break;
         case 101: $result = $updowSign * ($uBase + ($casterLevel / 2)); break;
         case 102: $result = $updowSign * ($uBase + $casterLevel); break;
         case 103: $result = $updowSign * ($uBase + ($casterLevel * 2)); break;
         case 104: $result = $updowSign * ($uBase + ($casterLevel * 3)); break;
         case 105: $result = $updowSign * ($uBase + ($casterLevel * 4)); break;
         case 107: $result = 
      }
   }

   public function calculateBuffDuration($casterLevel, $formula, $duration)
   {
      if ($formula >= 200) { return $formula; }
 
      switch ($formula) {
         case 0: return 0;
         case 1: $uDuration = $casterLevel / 2;
                 return ($uDuration < $duration) ? (($uDuration < 1 ? : $uDuration) : $duration;
         case 2: $uDuration = ($casterLevel <= 1) ? 6 : ($casterLevel / 2) + 5;
                 return ($uDuration < $duration) ? (($uDuration < 1 ? : $uDuration) : $duration;
         case 3: $uDuration = $casterLevel * 30;
                 return ($uDuration < $duration) ? (($uDuration < 1 ? : $uDuration) : $duration;
         case 4: $uDuration = 50;
                 return ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
      }
   }

   public function getAllSlots()
   {
      $return = array();
  
      for ($slot = 1; $slot <= 12; $slot++) {
         $slotInfo = $this->getSlotInfo($slot);

         if ($slotInfo) { $return[$slot] = $slotInfo; }
      }

      return $return;
   }

   public function getSlotInfo($slotNumber)
   {
      // There are only 12 slots, 1-12
      if ($slotNumber < 1 || $slotNumber > 12) { return false; }

      // No slot effect is set
      if ($this->data['effectid'.$slotNumber] == 254) { return null; }

      $return = array(
         'base'     => $this->data['base'.$slotNumber],
         'max'      => $this->data['max'.$slotNumber],
         'formula'  => $this->data['formula'.$slotNumber], 
         'effectId' => $this->data['effectid'.$slotNumber],
      );

      return 
   }
}
?>
