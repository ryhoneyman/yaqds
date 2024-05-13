<?php
// Copyright 2023 - Ryan Honeyman

include_once 'common/base.class.php';

class Entity extends LWPLib\Base
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



   public function isPlayer(?bool $isPlayer = null, ?bool $clear = null)
   {
      return $this->var('isPlayer',$isPlayer,$clear);
   }

   public function class(?string $class = null, ?bool $clear = null)
   {
      return $this->var('class',$class,$clear);
   }

   public function name(?string $name = null, ?bool $clear = null)
   {
      return $this->var('name',$name,$clear);
   }

   public function level(?int $level = null, ?bool $clear = null)
   {
      return $this->var('level',$level,$clear);
   }

   public function offense(?int $value = null, ?bool $clear = null)
   {
      return $this->var('offense',$value,$clear);
   }

   public function defense(?int $value = null, ?bool $clear = null)
   {
      return $this->var('defense',$value,$clear);
   }

   public function agi(?int $value = null, ?bool $clear = null)
   {
      return $this->var('agi',$value,$clear);
   }

   public function primaryHitSkillValue(?int $value = null, ?bool $clear = null)
   {
      return $this->var('primaryHitSkillValue',$value,$clear);
   }

   public function combatAgility(?int $value = null, ?bool $clear = null)
   {
      if (!$this->isPlayer()) { return 0; }

      return $this->var('combatAgility',$value,$clear);
   }

   public function accuracy(?int $value = null, ?bool $clear = null)
   {
      if ($this->isPlayer()) { return 0; }

      return $this->var('accuracy',$value,$clear);
   }

   public function intoxication(?int $value = null, ?bool $clear = null)
   {
      if (!$this->isPlayer()) { return 0; }
      
      return $this->var('intoxication',$value,$clear);
   }

   public function isBerserk(?bool $isBerserk = null, ?bool $clear = null)
   {
      return $this->var('isBerserk',$isBerserk,$clear);
   }

   public function itemBonuses(string $type, ?int $value = null, ?bool $clear = null)
   {
      return $this->var("itemBonuses.$type",$value,$clear);
   }

   public function spellBonuses(string $type, ?int $value = null, ?bool $clear = null)
   {
      return $this->var("spellBonuses.$type",$value,$clear);
   }

   public function aaBonuses(string $type, ?int $value = null, ?bool $clear = null)
   {
      return $this->var("aaBonuses.$type",$value,$clear);
   }

   public function itemModBonuses(string $type, ?int $value = null, ?bool $clear = null)
   {
      return $this->var("itemModBonuses.$type",$value,$clear);
   }

   public function buildNpc(int $level, int $accuracy, int $class): void
   {
      $baseSkill   = ($level > 50) ? 250 : min($level*5,210);
      $weaponSkill = $baseSkill;
      
      if (CURRENT_EXPANSION >= EXPANSION_PLANES) {
         $weaponSkill = ($level > 50) ? 250 + $level : min($level*6,275);
      } 

      $this->level($level);
      $this->class($class);
      $this->primaryHitSkillValue($weaponSkill);
      $this->defense($level * 5);
      $this->offense($level * 5);
      $this->agi($level * 5);
      $this->accuracy($accuracy);
   }

}   
