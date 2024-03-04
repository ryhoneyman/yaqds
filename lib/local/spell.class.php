<?php
// Copyright 2023 - Ryan Honeyman

include_once 'common/base.class.php';

class Spell extends Base
{
   protected $version   = 1.0;
   protected $data      = array();
   protected $id        = null;
   protected $valid     = false;
   protected $classes   = array();

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class.php
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);

      if ($options['data']) { $this->load($options['data']); }
   }
 
   public function calculateEffectValue($slotNumber, $casterLevel, $ticsRemaining = null, $instrumentMod = null)
   {
      if (!$this->valid) { return false; }

      $slotInfo = $this->getSlotInfo($slotNumber);

      if (!$slotInfo) { return 0; }

      $base       = $slotInfo['base'];
      $formula    = $slotInfo['formula'];
      $max        = $slotInfo['max'];

      // There's a BARD Jamfest AA snippet of code in this spot that we don't need right now.

      $effectValue = $this->calculateEffectValueFormula($formula,$base,$max,$casterLevel,$ticsRemaining);

      if ($this->isBardSong() && $this->isInstrumentModdableEffect($slotNumber)) { 
         $origValue   = $effectValue;
         $effectValue = $effectValue * ($instrumentMod / 10);

         $this->debug(9,"Bard instrument modified: origValue($origValue) effectValue($effectValue)");
      }

      return $effectValue;
   }

   public function calculateEffectValueFormula($formula, $base, $max, $casterLevel, $ticsRemaining = null)
   {
      $result     = 0;
      $updownSign = 1;
      $uBase      = $base;
      
      if ($uBase < 0) { $uBase = -($uBase); }

      if ($max < $base && $max != 0) { $updownSign = -1; }

      switch ($formula) {
         case 0:
         case 100: $result = $uBase; break;
         case 101: $result = $updownSign * ($uBase + ($casterLevel / 2)); break;
         case 102: $result = $updownSign * ($uBase + $casterLevel); break;
         case 103: $result = $updownSign * ($uBase + ($casterLevel * 2)); break;
         case 104: $result = $updownSign * ($uBase + ($casterLevel * 3)); break;
         case 105: $result = $updownSign * ($uBase + ($casterLevel * 4)); break;
         case 107: $result = '';
      }

      $origResult = $result;

      if ($max != 0 && (($updownSign == 1 && $result > $max) || ($updownSign != 1 && $result < $max))) { $result = $max; }

      if ($base < 0 && $result > 0) { $result *= -1; }

      $this->debug(9,sprintf("casterLevel(%d) ticsRemaining(%d) base(%d) uBase(%d) formula(%d) max(%d) updownSign(%d) result(%d) origResult(%d) %s",
                             $casterLevel,$ticsRemaining,$result,$base,$uBase,$formula,$max,$origResult,($base < 0 && $result > 0) ? "Inverted/negative base" : ''));

      return $result;
   }

   public function calculateBuffDurationFormula($casterLevel, $formula, $duration)
   {
      if ($formula >= 200) { return $formula; }

      $return = null;
 
      switch ($formula) {
         case 0: return 0;
         case 1:  $uDuration = $casterLevel / 2;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 2:  $uDuration = ($casterLevel <= 1) ? 6 : ($casterLevel / 2) + 5;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 3:  $uDuration = $casterLevel * 30;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 4:  $uDuration = 50;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 5:  $uDuration = 2;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 6:  $uDuration = ($casterLevel / 2) + 2;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 7:  $uDuration = $casterLevel;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 8:  $uDuration = $casterLevel + 10;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 9:  $uDuration = ($casterLevel * 2) + 10;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 10: $uDuration = ($casterLevel * 3) + 10;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 11: $uDuration = ($casterLevel * 30) + 90;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 12: $uDuration = $casterLevel / 4; 
                  $uDuration = ($uDuration) ? $uDuration : 1;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 50: $return = hexdec('0xFFFE');
                  break;
         default: $return = 0;
      }

      $this->debug(9,sprintf("casterLevel(%d) formula(%d) duration(%d) uDuration(%d) return(%d)",$casterLevel,$formula,$duration,$uDuration,$return));

      return $return;
   }

   /**
    * isInstrumentModdableEffect
    *
    * @param  integer $slotNumber
    * @return boolean
    */
   public function isInstrumentModdableEffect($slotNumber)
   {
      if (!$this->valid) { return false; }

      $slotInfo = $this->getSlotInfo($slotNumber);
      $effectId = $slotInfo['effectid'];
      $return   = false;

      switch ($effectId)
      {
         case SE_CurrentHP:
         case SE_ArmorClass:
         case SE_ATK: // Jonthan's Provocation, McVaxius` Rousing Rondo, Jonthan's Inspiration, Warsong of Zek
         case SE_MovementSpeed:	// maybe only positive values should be modded? Selo`s Consonant Chain uses this for snare
         case SE_STR:
         case SE_DEX:
         case SE_AGI:
         case SE_STA:
         case SE_INT:
         case SE_WIS:
         case SE_CHA:
         case SE_Stamina:
         case SE_ResistFire:
         case SE_ResistCold:
         case SE_ResistPoison:
         case SE_ResistDisease:
         case SE_ResistMagic:
         case SE_Rune: // Shield of Songs, Nillipus` March of the Wee
         case SE_DamageShield: // Psalm of Warmth, Psalm of Vitality, Psalm of Cooling, Psalm of Purity, McVaxius` Rousing Rondo, Warsong of Zek, Psalm of Veeshan
         case SE_AbsorbMagicAtt: // Psalm of Mystic Shielding, Niv`s Melody of Preservation, Shield of Songs, Niv`s Harmonic
         case SE_ResistAll: // Psalm of Veeshan
            $return = true;
            break;
         case SE_CurrentMana:
         {
            // Only these mana songs are moddable: Cassindra`s Chorus of Clarity, Denon`s Dissension, Cassindra`s Chant of Clarity, Ervaj's Lost Composition
            // but we override the mod for the mana regen songs in Mob::GetInstrumentMod()
            $targetType = $this->targetType();
            if ($this->buffDurationFormula() == 0 && $targetType != ST_Tap && $targetType != ST_TargetAETap) { $return = true; }
            break;
         }
      }

      $this->debug(9,sprintf("slotNumber(%d) effectId(%d) return(%s)",$slotNumber,$effectId,json_encode($return)));

      return $return;
   }
  
   /**
    * getAllSlots
    *
    * @return array|false
    */
   public function getAllSlots()
   {
      if (!$this->valid) { return false; }

      $return = array();
  
      for ($slot = 1; $slot <= SPELL_EFFECT_COUNT; $slot++) {
         $slotInfo = $this->getSlotInfo($slot);

         if ($slotInfo) { $return[$slot] = $slotInfo; }
      }

      return $return;
   }

   public function getSlotInfo($slotNumber)
   {
      if (!$this->valid) { return false; }

      if ($slotNumber < 1 || $slotNumber > SPELL_EFFECT_COUNT) { return false; }

      // No slot effect is set
      if ($this->data['effectid'.$slotNumber] == SE_Blank) { return null; }

      $return = array(
         'base'     => $this->data['base'.$slotNumber],
         'max'      => $this->data['max'.$slotNumber],
         'formula'  => $this->data['formula'.$slotNumber], 
         'effectId' => $this->data['effectid'.$slotNumber],
      );

      return $return;
   }

   public function isBardSong() { return ($this->classes[CLASS_BARD]) ? true : false; }

   public function buffDuration()        { return $this->property('buffduration'); }
   public function buffDurationFormula() { return $this->property('buffdurationformula'); }
   public function targetType()          { return $this->property('targettype'); }

   public function property($name) { return $this->data[$name]; }

   public function load($spellData) { 
      if (!preg_match('/^\d+$/',$spellData['id'])) { $this->error('Invalid spell data: id not valid'); return false; }

      $this->data = $spellData;

      $this->valid = true;
      $this->id    = $spellData['id'];

      for ($class = 1; $class <= CLASS_MAX_COUNT; $class++) {
         $classLevel = $this->data['class'.$class];

         if ($classLevel < SPELL_LEVEL_CANNOT_USE) { $this->classes[$class] = $classLevel; }
      }
      
   }
}
