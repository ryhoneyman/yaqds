<?php
// Copyright 2023 - Ryan Honeyman

include_once 'common/base.class.php';
include_once 'local/random.class.php';

class Attack extends LWPLib\Base
{
   protected $version = 1.0;
   protected $random  = null;

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class.php
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);

      $this->random = new Random($debug,$options);
   }

   public function avoidanceCheck(Entity $attacker, Entity $defender): bool
   {
      $hitChance = 0;
      $toHit     = $this->getToHit($attacker) + 10;
      $avoidance = $this->getAvoidance($defender) + 10;

      // SKIPPED: percentMod in this spot controls modification based on discipline use - we'll address this later

      if ($toHit * 1.21 > $avoidance) { $hitChance = 1 - ($avoidance / ($toHit * 1.21 * 2)); }
      else { $hitChance = $toHit * 1.21 / ($avoidance * 2); }

      $roll = $this->random->randReal();
      
      //$this->debug(9,"roll:$roll, hitChance:$hitChance");

      if ($roll < $hitChance) { return true; }  // Defender was hit

      // SKIPPED: player skill increase check happens in this spot

      return false;
   }

   public function getToHit(Entity $attacker): float
   {
      // attacker(player) uses offense, primaryHitSkillValue, *bonuses, intoxication, class, level, isBerserk
      // attacker(npc) uses accuracy and level.
      $accuracy = 0;
      $toHit    = 7 + $attacker->offense() + $attacker->primaryHitSkillValue();

      if ($attacker->isPlayer()) {
         $accuracy = $attacker->itemBonuses('Accuracy') + $attacker->spellBonuses('Accuracy') + $attacker->aaBonuses('Accuracy') + $attacker->itemModBonuses('HitChance');

         $drunkFactor = $attacker->intoxication() / 2;

         if ($drunkFactor > 20) {
            $drunkReduction = 110 - $drunkFactor;
   
            if ($drunkReduction > 100) { $drunkReduction = 100; }
   
            $toHit = ($toHit * $drunkReduction) / 100;
         }
         else if ($attacker->class() == CLASS_WARRIOR && $attacker->isBerserk()) { $toHit += 2 * ($attacker->level() / 5); }
   
      }
      else {
         $accuracy = $attacker->accuracy();

         if ($attacker->level() < 3) { $accuracy += 2; }
      }

      $toHit += $accuracy;

      //$this->debug(9,"toHit:$toHit");

      return $toHit;
   }
 
   public function getAvoidance(Entity $defender): float
   {
      $computedDefense  = 1;
      $defenseAvoidance = 0;
      $agiAvoidance     = 0;
      $drunkFactor      = $defender->intoxication() / 2;
      $defenderDefense  = $defender->defense();
      $defenderAgi      = $defender->agi();
      $defenderLevel    = $defender->level();

      if ($defenderDefense > 0) { $defenseAvoidance = $defenderDefense * 400 / 225; }

      if ($defenderAgi < 40)                             { $agiAvoidance = (25 * ($defenderAgi - 40)) / 40; }
      else if ($defenderAgi >= 60 && $defenderAgi <= 74) { $agiAvoidance = (2 * (28 - ((200 - $defenderAgi) / 5))) / 3; }
      else if ($defenderAgi >= 75) {
         $bonusAdj = 80;

         if ($defenderLevel < 7)       { $bonusAdj = 35; }
         else if ($defenderLevel < 20) { $bonusAdj = 55; }
         else if ($defenderLevel < 40) { $bonusAdj = 70; }

         if ($defenderAgi < 200) { $agiAvoidance = (2 * ($bonusAdj - ((200 - $defenderAgi) / 5))) / 3; }
         else                    { $agiAvoidance = 2 * $bonusAdj / 3; }
      }

      $computedDefense = $defenseAvoidance + $agiAvoidance;
      $computedDefense += ($computedDefense * $defender->combatAgility()) / 100;   // Combat Agility AA percent increase

      if ($drunkFactor > 20) {
         $drunkMultiplier = 110 - $drunkFactor;

         if ($drunkMultiplier > 100) { $drunkMultiplier = 100; }

         $computedDefense = ($computedDefense * $drunkMultiplier) / 100;
      }

      if ($computedDefense < 1)  { $computedDefense = 1; }

      //$this->debug(9,"computedDefense:$computedDefense, defenseAvoidance:$defenseAvoidance, agiAvoidance:$agiAvoidance");

      return $computedDefense;
   }
}   
