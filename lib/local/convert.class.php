<?php
// Copyright 2023 - Ryan Honeyman

include_once 'common/base.class.php';

class Convert extends LWPLib\Base
{
   protected $version   = 1.0;

   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);
   }

   public function convertNpcSpecialAbilities($specialAbilities = '')
   {
      $abilityMap = [
         '1' => [
            'name' => 'Summon',
            'params' => [
               ['name' => 'enabled', 'default' => 1],
               ['name' => 'cooldown_ms', 'default' => 6000],
               ['name' => 'hp_ratio', 'default' => 97],
            ],
         ],
         '2' => [
            'name' => 'Enrage',
            'params' => [
               ['name' => 'hp_percent', 'default' => RULE_NPC_STARTENRAGEVALUE],  // 10
               ['name' => 'duration_ms', 'default' => 10000],
               ['name' => 'cooldown_ms', 'default' => 360000],
            ],
         ],
         '3' => [
            'name' => 'Rampage',
            'params' => [
               ['name' => 'percent_chance', 'default' => 20],
               ['name' => 'target_count', 'default' => RULE_COMBAT_MAXRAMPAGETARGET],  // 3
               ['name' => 'percent_damage', 'default' => 100],
               ['name' => 'flat_added_damage', 'default' => 0],
               ['name' => 'percent_ac_ignore', 'default' => 0],
               ['name' => 'flat_ac_ignore', 'default' => 0],
               ['name' => 'percent_natural_crit', 'default' => 100],
               ['name' => 'flat_added_crit', 'default' => 0],
            ],
         ],
         '4' => [
            'name' => 'Area Rampage',
         ],
         '5' => [
            'name' => 'Flurry',
         ],
         '6' => [
            'name' => 'Triple Attack',
         ],
         '7' => [
            'name' => 'Dual Wield',
         ],
         '8' => [
            'name' => 'Do Not Equip',
         ],
         '9' => [
            'name' => 'Bane Attack',
         ],
         '10' => [
            'name' => 'Magical Attack',
         ],
         '11' => [
            'name' => 'Ranged Attack',
         ],
         '12' => [
            'name' => 'Unslowable',
         ],
         '13' => [
            'name' => 'Unmezable',
         ],
         '14' => [
            'name' => 'Uncharmable',
         ],
         '15' => [
            'name' => 'Unstunable',
         ],
         '16' => [
            'name' => 'Unsnareable',
         ],
         '17' => [
            'name' => 'Unfearable',
         ],
         '18' => [
            'name' => 'Immune to Dispell',
         ],
         '19' => [
            'name' => 'Immune to Melee',
         ],
         '20' => [
            'name' => 'Immune to Magic',
         ],
         '21' => [
            'name' => 'Immune to Fleeing',
         ],
         '22' => [
            'name' => 'Immune to Non-Bane Damage',
         ],
         '23' => [
            'name' => 'Immune to Non-Magical Damage',
         ],
         '24' => [
            'name' => 'Will Not Aggro',
         ],
         '25' => [
            'name' => 'Immune to Aggro',
         ],
         '26' => [
            'name' => 'Resist Ranged Spells',
         ],
         '27' => [
            'name' => 'See Through Feign Death',
         ],
         '28' => [
            'name' => 'Immune to Taunt',
         ],
         '29' => [
            'name' => 'Tunnel Vision',
         ],
         '30' => [
            'name' => 'Does Not Buff/Heal Friends',
         ],
         '31' => [
            'name' => 'Unpacifiable',
         ],
         '32' => [
            'name' => 'Leashed',
         ],
         '33' => [
            'name' => 'Tethered',
         ],
         '34' => [
            'name' => 'Permaroot Flee',
         ],
         '35' => [
            'name' => 'No Harm from Players',
         ],
         '36' => [
            'name' => 'Always Flee',
         ],
         '37' => [
            'name' => 'Flee Percentage',
         ],
         '38' => [
            'name' => 'Allow Beneficial',
         ],
         '39' => [
            'name' => 'Disable Melee',
         ],      
         '40' => [
            'name' => 'Chase Distance',
         ],
         '41' => [
            'name' => 'Allow Tank',
         ],
         '42' => [
            'name' => 'Proximity Aggro',
         ],
         '43' => [
            'name' => 'Always Call for Help',
         ],
         '44' => [
            'name' => 'Use Warrior Skills',
         ],
         '45' => [
            'name' => 'Always Flee Low Con',
         ],
         '46' => [
            'name' => 'No Loitering',
         ],
         '47' => [
            'name' => 'Bad Faction Block Hand In',
         ],
         '48' => [
            'name' => 'PC Deathblow Corpse',
         ],
         '49' => [
            'name' => 'Corpse Camper',
         ],
         '50' => [
            'name' => 'Reverse Slow',
         ],
         '51' => [
            'name' => 'No Haste',
         ],
         '52' => [
            'name' => 'Immune to Disarm',
         ],
         '53' => [
            'name' => 'Immune to Riposte',
         ],
         '54' => [
            'name' => 'Proximity Aggro',
         ],
         '55' => [
            'name' => 'Max Special Attack',
         ],
      ];
   
      $abilityList   = explode('^',$specialAbilities);
      $convertedList = [];
   
      foreach ($abilityList as $ability) {
         $abilityProperties = explode(',',$ability);
         $abilityType       = $abilityProperties[0];
         $abilityName       = $abilityMap[$abilityType]['name'] ?: 'Unknown';
         $abilityEnabled    = ($abilityProperties[0] == '0') ? false : true;
   
         if ($abilityEnabled) { $convertedList[] = $abilityName; }
      }
   
      if (!$convertedList) { return "None"; }
   
      return implode(', ',array_unique($convertedList));
   }
   
   public function convertExpansionName($expansionNumber)
   {
      $expansionList = [
         '-1' => 'Any',
         '0'  => 'Classic',
         '1'  => 'Kunark',
         '2'  => 'Velious',
         '3'  => 'Luclin',
         '4'  => 'Planes',
         '5'  => 'PostPlanes',
         '6'  => 'Disabled',
         '7'  => 'Disabled',
         '99' => 'Any',
      ];
   
      $minorList = [
         ''  => '',
         '0' => '',
         '3' => 'II',
         '6' => 'III',
         '9' => 'IV',
      ];
   
      list($major,$minor) = explode('.',$expansionNumber);

      $majorExpansion = (isset($expansionList[$major])) ? $expansionList[$major] : 'Unknown';
      $minorExpansion = (isset($minorList[$minor])) ? $minorList[$minor] : '';
   
      return sprintf("%s%s",$majorExpansion,$minorExpansion);
   }
   
   public function convertGridType($value, $type)
   {
      $type = strtolower($type);
   
      $expansionList = [
         'wander' => [
            '0' => 'Circular',
            '1' => 'Random10',
            '2' => 'Random',
            '3' => 'Patrol',
            '4' => 'OneWayRepop',
            '5' => 'Random5LoS',
            '6' => 'OneWayDepop',
            '7' => 'CenterPoint',
            '8' => 'RandomCenterPoint',
            '9' => 'RandomPath',
         ],
         'pause' => [
            '0' => 'RandomHalf',
            '1' => 'Full',
            '2' => 'Random',
         ],
      ];
   
      return ((isset($expansionList[$type][$value])) ? $expansionList[$type][$value] : null);
   }

   public function convertSpellEffectList($spellData)
   {
      $spellEffectList = [];

      for ($effectPos = 1; $effectPos <= 12; $effectPos++) {
         $effectId      = $spellData['effectid'.$effectPos];
         $effectBase    = $spellData['effect_base_value'.$effectPos];
         $effectLimit   = $spellData['effect_limit_value'.$effectPos];
         $effectMax     = $spellData['max'.$effectPos];
         $effectFormula = $spellData['formula'.$effectPos];

         // Effect ID 10 (SE_CHA) when set to all zeroes is used as a placeholder/spacer
         if ($effectId == 254 || ($effectId == 10 && $effectBase == 0 && $effectMax == 0)) { continue; }

         $spellEffectList[$effectPos] = [
            'id'               => $effectId,
            'base'             => $effectBase,
            'limit'            => $effectLimit,
            'max'              => $effectMax,
            'formula'          => $effectFormula,
            'effectName'       => $this->convertSpellEffect($effectId,'name'),
            'effectDisplay'    => $this->convertSpellEffect($effectId,'display'),
            'effectExceptions' => $this->convertSpellEffect($effectId,'exceptions'),
         ];
      }

      return $spellEffectList;
   }

   public function convertSpellClasses($spellData)
   {
      $classList = [];

      if (!isset($spellData['classes1'])) { return $classList; }

      for ($classId = 1; $classId <= 15; $classId++) {
         $classLevel = $spellData['classes'.$classId];
         if ($classLevel < 255) { $classList[$this->convertClass($classId)] = $classLevel; }
      }

      return $classList;
   }

   public function convertSpellEffect($spellEffectId, $return = null)
   {
      $spellEffectList = [
         '0' => [
            'name'    => 'SE_CurrentHP',
            'display' => ['format' => 1, 'label' => 'Hitpoints', 'allowDuration' => true],
         ],
         '1' => [
            'name'    => 'SE_ArmorClass',
            'display' => ['format' => 2, 'label' => 'AC'],
         ],
         '2' => [
            'name'    => 'SE_ATK',
            'display' => ['format' => 1, 'label' => 'Attack'],
         ],
         '3' => [
            'name'    => 'SE_MovementSpeed',
            'display' => ['format' => 1, 'label' => 'Movement Speed', 'values' => ['effect:units' => 'raw^%']],
         ],
         '4' => [
            'name'    => 'SE_STR',
            'display' => ['format' => 1, 'label' => 'Strength'],
         ],
         '5' => [
            'name'    => 'SE_DEX',
            'display' => ['format' => 1, 'label' => 'Dexerity'],
         ],
         '6' => [
            'name'    => 'SE_AGI',
            'display' => ['format' => 1, 'label' => 'Agility'],
         ],
         '7' => [
            'name'    => 'SE_STA',
            'display' => ['format' => 1, 'label' => 'Stamina'],
         ],
         '8' => [
            'name'    => 'SE_INT',
            'display' => ['format' => 1, 'label' => 'Intelligence'],
         ],
         '9' => [
            'name'    => 'SE_WIS',
            'display' => ['format' => 1, 'label' => 'Wisdom'],
         ],
         '10' => [
            'name'    => 'SE_CHA',
            'display' => ['format' => 1, 'label' => 'Charisma'],
         ],
         '11' => [
            'name'    => 'SE_AttackSpeed',
            'display' => ['format' => 1, 'label' => 'Attack Speed'],
         ],
         '12' => [
            'name'    => 'SE_Invisibility',
            'display' => ['format' => 0, 'label' => 'Invisibility'],
         ],
         '13' => [
            'name'    => 'SE_SeeInvis',
            'display' => ['format' => 0, 'label' => 'See Invisibile'],
         ],
         '14' => [
            'name'    => 'SE_WaterBreathing',
            'display' => ['format' => 0, 'label' => 'Water Breathing'],
         ],
         '15' => [
            'name'    => 'SE_CurrentMana',
            'display' => ['format' => 1, 'label' => 'Mana', 'allowDuration' => true],
         ],
         '18' => [
            'name'    => 'SE_Lull',
            'display' => ['format' => 0, 'label' => 'Pacify'],
         ],
         '19' => [
            'name'    => 'SE_AddFaction',
            'display' => ['format' => 1, 'label' => 'Faction'],
         ],
         '20' => [
            'name'    => 'SE_Blind',
            'display' => [
               'format' => 8, 
               'label' => 'Blindness',
               'values' => [
                  'strength' => 'conv^convertBlindnessStackingValue^{{effect:base}}'
               ]
            ],
         ],
         '21' => [
            'name'    => 'SE_Stun',
            'display' => ['format' => 9, 'label' => 'Stun'],
         ],
         '22' => [
            'name'    => 'SE_Charm',
            'display' => ['format' => 10, 'label' => 'Charm'],
         ],
         '23' => [
            'name'    => 'SE_Fear',
            'display' => ['format' => 10, 'label' => 'Fear'],
         ],
         '24' => [
            'name' => 'SE_Stamina',
            'display' => ['format' => 1, 'label' => 'Endurance', 'allowDuration' => true, 'reverseAdjust' => true],
         ],
         '25' => [
            'name'    => 'SE_BindAffinity',
            'display' => [
               'format' => 'Bind Location: {{bindType}}', 
               'values' => [
                  'bindType' => 'conv^convertBindTypeById^{{effect:base}}',
               ],
            ],
         ],
         '26' => [
            'name'    => 'SE_Gate',
            'display' => [
               'format' => 'Teleport to Bound Location: {{bindType}}', 
               'values' => [
                  'bindType' => 'conv^convertBindTypeById^{{effect:base}}',
               ],
            ],
         ],
         '27' => [
            'name'    => 'SE_CancelMagic',
            'display' => [
               'format' => 'Dispel Magic: (+{{effect:base}} to caster level effectiveness)', 
            ],
         ],
         '28' => [
            'name' => 'SE_InvisVsUndead',
            'display' => ['format' => 0, 'label' => 'Invisibility versus Undead'],
         ],
         '29' => [
            'name'    => 'SE_InvisVersusAnimals',
            'display' => ['format' => 0, 'label' => 'Invisibility versus Animals'],
         ],
         '30' => [
            'name'    => 'SE_ChangeFrenzyRadius',
            'display' => ['format' => 6, 'label' => 'Aggro Radius'],
         ],
         '31' => [
            'name'    => 'SE_Mez',
            'display' => ['format' => 3, 'label' => 'Mezmerize'],
         ],
         '32' => [
            'name'    => 'SE_SummonItem',
            'display' => [
               'format' => 'Summon: {{itemName}}', 
               'values' => [
                  'itemName' => 'data^getItemInfoById^{{effect:base}}^Name',
               ],
            ],
         ],
         '33' => [
            'name'    => 'SE_SummonPet',
            'display' => ['format' => 11, 'label' => 'Summon Pet'],
         ],
         '35' => [
            'name'    => 'SE_DiseaseCounter',
            'display' => ['format' => 1, 'label' => 'Disease Counter'],
         ],
         '36' => [
            'name'    => 'SE_PoisonCounter',
            'display' => ['format' => 1, 'label' => 'Poison Counter'],
         ],
         '40' => [
            'name'    => 'SE_DivineAura',
            'display' => ['format' => 0, 'label' => 'Invulnerability'],
         ],
         '41' => [
            'name'    => 'SE_Destroy',
            'display' => ['format' => 'Destroy {{spell:targetTypeName}} up to L51'],
         ],
         '42' => [
            'name'    => 'SE_ShadowStep',
            'display' => ['format' => 0, 'label' => 'Shadow Step'],
         ],
         '43' => [
            'name'    => 'SE_Berserk',
            'display' => ['format' => 0, 'label' => 'Berserk'],
         ],
         '44' => [
            'name'    => 'SE_Lycanthropy',
            'display' => ['format' => 0, 'label' => 'Delayed Heal Marker'],
         ],
         '45' => [
            'name'    => 'SE_Vampirism',
            'display' => ['format' => 0, 'label' => 'Melee Lifetap'],
         ],
         '46' => [
            'name' => 'SE_ResistFire',
            'display' => ['format' => 1, 'label' => 'Fire Resistance'],
         ],
         '47' => [
            'name' => 'SE_ResistCold',
            'display' => ['format' => 1, 'label' => 'Cold Resistance'],
         ],
         '48' => [
            'name' => 'SE_ResistPoison',
            'display' => ['format' => 1, 'label' => 'Poison Resistance'],
         ],
         '49' => [
            'name' => 'SE_ResistDisease',
            'display' => ['format' => 1, 'label' => 'Disease Resistance'],
         ],
         '50' => [
            'name' => 'SE_ResistMagic',
            'display' => ['format' => 1, 'label' => 'Magic Resistance'],
         ],
         '52' => [
            'name'    => 'SE_SenseDead',
            'display' => ['format' => 0, 'label' => 'Sense Undead'],
         ],
         '53' => [
            'name'    => 'SE_SenseSummoned',
            'display' => ['format' => 0, 'label' => 'Sense Summoned'],
         ],
         '54' => [
            'name'    => 'SE_SenseAnimals',
            'display' => ['format' => 0, 'label' => 'Sense Animals'],
         ],
         '55' => [
            'name' => 'SE_Rune',
            'display' => ['format' => 1, 'label' => 'Melee Damage Absorbance'],
         ],
         '56' => [
            'name'    => 'SE_TrueNorth',
            'display' => ['format' => 0, 'label' => 'True North'],
         ],
         '57' => [
            'name'    => 'SE_Levitate',
            'display' => ['format' => 0, 'label' => 'Levitate'],
         ],
         '58' => [
            'name' => 'SE_Illusion',
            'display' => [
               'format' => 'Illusion: {{raceName}}', 
               'values' => [
                  'raceName' => 'conv^convertRace^{{effect:base}}',
               ],
            ],
         ],
         '59' => [
            'name' => 'SE_DamageShield',
            'display' => ['format' => 1, 'label' => 'Damage Shield', 'reverseAdjust' => true],
         ],
         '61' => [
            'name'    => 'SE_Identify',
            'display' => ['format' => 0, 'label' => 'Identify'],
         ],
         '63' => [
            'name'    => 'SE_WipeHateList',
            'display' => ['format' => 4, 'label' => 'Memblur'],
         ],
         '64' => [
            'name'    => 'SE_SpinTarget',
            'display' => ['format' => 0, 'label' => 'Spin Stun'],
         ],
         '65' => [
            'name'    => 'SE_Infravision',
            'display' => ['format' => 0, 'label' => 'Infravision'],
         ],
         '66' => [
            'name'    => 'SE_Ultravision',
            'display' => ['format' => 0, 'label' => 'Ultravision'],
         ],
         '67' => [
            'name' => 'SE_EyeOfZoom',
            'text' => 'foo'
         ],
         '68' => [
            'name'    => 'SE_ReclaimPet',
            'display' => ['format' => 0, 'label' => 'Reclaim Pet'],
         ],
         '69' => [
            'name'    => 'SE_TotalHP',
            'display' => ['format' => 1, 'label' => 'Maximum Hitpoints'],
         ],
         '71' => [
            'name' => 'SE_NecPet',
            'text' => 'foo'
         ],
         '73' => [
            'name'    => 'SE_Bindsight',
            'display' => ['format' => 0, 'label' => 'Bind Sight'],
         ],
         '74' => [
            'name'    => 'SE_FeignDeath',
            'display' => ['format' => 0, 'label' => 'Feign Death'],
         ],
         '75' => [
            'name'    => 'SE_VoiceGraft',
            'display' => ['format' => 0, 'label' => 'Voice Graft'],
         ],
         '76' => [
            'name'    => 'SE_Sentinel',
            'display' => ['format' => 0, 'label' => 'Sentinel'],
         ],
         '77' => [
            'name'    => 'SE_LocateCorpse',
            'display' => ['format' => 0, 'label' => 'Locate Corpse'],
         ],
         '78' => [
            'name' => 'SE_AbsorbMagicAttack',
            'text' => 'foo'
         ],
         '79' => [
            'name'    => 'SE_CurrentHPOnce',
            'display' => ['format' => 1, 'label' => 'Hitpoints initially'],
         ],
         '81' => [
            'name'    => 'SE_Revive',
            'display' => ['format' => 'Resurrect and restore {{effect:base}}% experience'],
         ],
         '82' => [
            'name'    => 'SE_SummonPC',
            'display' => ['format' => 0, 'label' => 'Summon Player'],
         ],
         '83' => [
            'name'    => 'SE_Teleport',
            'display' => ['format' => 5, 'label' => 'Teleport'],
         ],
         '84' => [
            'name' => 'SE_TossUp',
            'text' => 'foo'
         ],
         '85' => [
            'name' => 'SE_WeaponProc',
            'text' => 'foo'
         ],
         '86' => [
            'name'    => 'SE_Harmony',
            'display' => ['format' => 6, 'label' => 'Assist Radius'],
         ],
         '87' => [
            'name' => 'SE_MagnifyVision',
            'text' => 'foo'
         ],
         '88' => [
            'name' => 'SE_Succor',
            'text' => 'foo'
         ],
         '89' => [
            'name' => 'SE_ModelSize',
            'text' => 'foo'
         ],
         '91' => [
            'name'    => 'SE_SummonCorpse',
            'display' => ['format' => 'Summon Corpse up to L{{effect:base}}'],
         ],
         '92' => [
            'name' => 'SE_InstantHate',
            'text' => 'foo'
         ],
         '93' => [
            'name'    => 'SE_StopRain',
            'display' => ['format' => 0, 'label' => 'Stop Rain'],
         ],
         '94' => [
            'name' => 'SE_NegateIfCombat',
            'text' => 'foo'
         ],
         '95' => [
            'name'    => 'SE_Sacrifice',
            'display' => [
               'format' => 'Sacrifice Player between L{{sacrificeMinLevel}} and L{{sacrificeMaxLevel}}', 
               'values' => [
                  'sacrificeMinLevel' => 'data^getRuleInfoByName^Spells:SacrificeMinLevel^rule_value',
                  'sacrificeMaxLevel' => 'data^getRuleInfoByName^Spells:SacrificeMaxLevel^rule_value',
               ],
            ],
         ],
         '96' => [
            'name' => 'SE_Silence',
            'text' => 'foo'
         ],
         '97' => [
            'name' => 'SE_ManaPool',
            'display' => ['format' => 1, 'label' => 'Maximum Mana'],
         ],
         '98' => [
            'name' => 'SE_AttackSpeed2',
            'text' => 'foo'
         ],
         '99' => [
            'name'    => 'SE_Root',
            'display' => ['format' => 0, 'label' => 'Root'],
         ],
         '100' => [
            'name' => 'SE_HealOverTime',
            'text' => 'foo'
         ],
         '101' => [
            'name' => 'SE_CompleteHeal',
            'text' => 'foo'
         ],
         '102' => [
            'name' => 'SE_Fearless',
            'text' => 'foo'
         ],
         '103' => [
            'name' => 'SE_CallPet',
            'text' => 'foo'
         ],
         '104' => [
            'name' => 'SE_Translocate',
            'display' => ['format' => 5, 'label' => 'Translocate'],
         ],
         '105' => [
            'name' => 'SE_AntiGate',
            'text' => 'foo'
         ],
         '106' => [
            'name' => 'SE_SummonBSTPet',
            'text' => 'foo'
         ],
         '107' => [
            'name' => 'SE_AlterNPCLevel',
            'text' => 'foo'
         ],
         '108' => [
            'name' => 'SE_Familiar',
            'text' => 'foo'
         ],
         '109' => [
            'name' => 'SE_SummonItemIntoBag',
            'text' => 'foo'
         ],
         '110' => [
            'name' => 'SE_IncreaseArchery',
            'text' => 'foo'
         ],
         '111' => [
            'name' => 'SE_ResistAll',
            'text' => 'foo'
         ],
         '112' => [
            'name' => 'SE_CastingLevel',
            'text' => 'foo'
         ],
         '113' => [
            'name' => 'SE_SummonHorse',
            'text' => 'foo'
         ],
         '114' => [
            'name' => 'SE_ChangeAggro',
            'text' => 'foo'
         ],
         '115' => [
            'name' => 'SE_Hunger',
            'text' => 'foo'
         ],
         '116' => [
            'name' => 'SE_CurseCounter',
            'text' => 'foo'
         ],
         '117' => [
            'name' => 'SE_MagicWeapon',
            'text' => 'foo'
         ],
         '118' => [
            'name' => 'SE_Amplification',
            'text' => 'foo'
         ],
         '119' => [
            'name' => 'SE_AttackSpeed3',
            'text' => 'foo'
         ],
         '120' => [
            'name' => 'SE_HealRate',
            'text' => 'foo'
         ],
         '121' => [
            'name' => 'SE_ReverseDS',
            'text' => 'foo'
         ],
         '123' => [
            'name' => 'SE_Screech',
            'text' => 'foo'
         ],
         '124' => [
            'name' => 'SE_ImprovedDamage',
            'text' => 'foo'
         ],
         '125' => [
            'name' => 'SE_ImprovedHeal',
            'text' => 'foo'
         ],
         '126' => [
            'name' => 'SE_SpellResistReduction',
            'text' => 'foo'
         ],
         '127' => [
            'name' => 'SE_IncreaseSpellHaste',
            'text' => 'foo'
         ],
         '128' => [
            'name' => 'SE_IncreaseSpellDuration',
            'text' => 'foo'
         ],
         '129' => [
            'name' => 'SE_IncreaseRange',
            'text' => 'foo'
         ],
         '130' => [
            'name' => 'SE_SpellHateMod',
            'text' => 'foo'
         ],
         '131' => [
            'name' => 'SE_ReduceReagentCost',
            'text' => 'foo'
         ],
         '132' => [
            'name' => 'SE_ReduceManaCost',
            'text' => 'foo'
         ],
         '133' => [
            'name' => 'SE_RFcStunTimeMod',
            'text' => 'foo'
         ],
         '145' => [
            'name' => 'SE_Teleport2',
            'text' => 'foo'
         ],
         '147' => [
            'name' => 'SE_PercentHeal',
            'text' => 'foo'
         ],
         '148' => [
            'name' => 'SE_StackingCommandBlock',
            'text' => 'foo'
         ],
         '149' => [
            'name' => 'SE_StackingCommandOverwrite',
            'text' => 'foo'
         ],
         '150' => [
            'name' => 'SE_DeathSave',
            'text' => 'foo'
         ],
         '151' => [
            'name' => 'SE_SuspendPet',
            'text' => 'foo'
         ],
         '152' => [
            'name' => 'SE_TemporaryPets',
            'text' => 'foo'
         ],
         '153' => [
            'name' => 'SE_BalanceHP',
            'text' => 'foo'
         ],
         '154' => [
            'name' => 'SE_DispelDetrimental',
            'text' => 'foo'
         ],
         '155' => [
            'name' => 'SE_SpellCritDmgIncrease',
            'text' => 'foo'
         ],
         '156' => [
            'name' => 'SE_IllusionCopy',
            'text' => 'foo'
         ],
         '157' => [
            'name' => 'SE_SpellDamageShield',
            'text' => 'foo'
         ],
         '158' => [
            'name' => 'SE_Reflect',
            'text' => 'foo'
         ],
         '159' => [
            'name' => 'SE_AllStats',
            'text' => 'foo'
         ],
         '161' => [
            'name' => 'SE_MitigateSpellDamage',
            'text' => 'foo'
         ],
         '162' => [
            'name' => 'SE_MitigateMeleeDamage',
            'text' => 'foo'
         ],
         '163' => [
            'name' => 'SE_NegateAttacks',
            'text' => 'foo'
         ],
         '167' => [
            'name' => 'SE_PetPowerIncrease',
            'text' => 'foo'
         ],
         '168' => [
            'name'    => 'SE_MeleeMitigation',
            'display' => ['format' => 7, 'label' => 'Incoming Melee Damage'],
         ],
         '169' => [
            'name'    => 'SE_CriticalHitChance',
            'display' => ['format' => 7, 'label' => 'Melee Critical Hit Chance'],
            'exceptions' => [
               '4499' => ['label' => 'Set Melee Critical Hit Chance to {{chance}}%', 'values' => ['chance' => 'raw^0']]
            ],
         ],
         '170' => [
            'name' => 'SE_SpellCritChance',
            'text' => 'foo'
         ],
         '171' => [
            'name' => 'SE_CripplingBlowChance',
            'text' => 'foo'
         ],
         '172' => [
            'name' => 'SE_AvoidMeleeChance',
            'text' => 'foo'
         ],
         '173' => [
            'name' => 'SE_RiposteChance',
            'text' => 'foo'
         ],
         '174' => [
            'name' => 'SE_DodgeChance',
            'text' => 'foo'
         ],
         '175' => [
            'name' => 'SE_ParryChance',
            'text' => 'foo'
         ],
         '176' => [
            'name' => 'SE_DualWieldChance',
            'text' => 'foo'
         ],
         '177' => [
            'name' => 'SE_DoubleAttackChance',
            'text' => 'foo'
         ],
         '178' => [
            'name' => 'SE_MeleeLifetap',
            'text' => 'foo'
         ],
         '179' => [
            'name' => 'SE_AllInstrumentMod',
            'text' => 'foo'
         ],
         '180' => [
            'name' => 'SE_ResistSpellChance',
            'text' => 'foo'
         ],
         '181' => [
            'name' => 'SE_ResistFearChance',
            'text' => 'foo'
         ],
         '182' => [
            'name' => 'SE_HundredHands',
            'text' => 'foo'
         ],
         '183' => [
            'name' => 'SE_MeleeSkillCheck',
            'text' => 'foo'
         ],
         '184' => [
            'name' => 'SE_HitChance',
            'text' => 'foo'
         ],
         '185' => [
            'name' => 'SE_DamageModifier',
            'display' => ['format' => 7, 'label' => 'Melee Damage'],
         ],
         '186' => [
            'name' => 'SE_MinDamageModifier',
            'text' => 'foo'
         ],
         '254' => [
            'name' => 'SE_Blank',
            'text' => 'foo'
         ],
      ];

      return (($return) ? $spellEffectList[$spellEffectId][$return] : $spellEffectList[$spellEffectId]) ?: null;
   }
   
   public function convertRace($raceId)
   {
      $raceList = [
         '1'   => 'Human',
         '2'   => 'Barbarian',
         '3'   => 'Erudite',
         '4'   => 'Wood Elf',
         '5'   => 'High Elf',
         '6'   => 'Dark Elf',
         '7'   => 'Half Elf',
         '8'   => 'Dwarf',
         '9'   => 'Troll',
         '10'  => 'Ogre',
         '11'  => 'Halfling',
         '12'  => 'Gnome',
         '14'  => 'Werewolf',
         '15'  => 'Brownie',
         '25'  => 'Fairy',
         '28'  => 'Fungusman',
         '42'  => 'Wolf',
         '43'  => 'Bear',
         '44'  => 'Freeport Guard',
         '48'  => 'Kobold',
         '49'  => 'Lava Dragon',
         '50'  => 'Lion',
         '52'  => 'Mimic',
         '55'  => 'Human Begger',
         '56'  => 'Pixie',
         '57'  => 'Dracnid',
         '60'  => 'Skeleton',
         '63'  => 'Tiger',
         '65'  => 'Vampire',
         '67'  => 'Highpass Citizen',
         '69'  => 'Wisp',
         '70'  => 'Zombie',
         '72'  => 'Ship',
         '73'  => 'Launch',
         '74'  => 'Froglok',
         '75'  => 'Elemental',
         '77'  => 'Neriak Citizen',
         '78'  => 'Erudite Citizen',
         '79'  => 'Bixie',
         '81'  => 'Rivervale Citizen',
         '88'  => 'Clockwork Gnome',
         '90'  => 'Halas Citizen',
         '91'  => 'Alligator',
         '92'  => 'Grobb Citizen',
         '93'  => 'Oggok Citizen',
         '94'  => 'Kaladim Citizen',
         '98'  => 'Elf Vampire',
         '106' => 'Felguard',
         '108' => 'Eye Of Zomm',
         '112' => 'Fayguard',
         '114' => 'Ghost Ship',
         '117' => 'Dwarf Ghost',
         '118' => 'Erudite Ghost',
         '120' => 'Wolf Elemental',
         '127' => 'Invisible Man',
         '128' => 'Iksar',
         '130' => 'Vahshir',
         '141' => 'Controlled Boat',
         '142' => 'Minor Illusion',
         '143' => 'Treeform',
         '145' => 'Goo',
         '158' => 'Wurm',
         '161' => 'Iksar Skeleton',
         '184' => 'Velious Dragon',
         '196' => 'Ghost Dragon',
         '198' => 'Prismatic Dragon',
         '209' => 'Earth Elemental',
         '210' => 'Air Elemental',
         '211' => 'Water Elemental',
         '212' => 'Fire Elemental',
         '216' => 'Horse',
         '240' => 'Teleport Man',
         '296' => 'Mithaniel Marr',
      ];

      return $raceList[$raceId] ?: null;
   }

   public function convertClass($classId)
   {
      $classList = [
         '1' => 'Warrior',
         '2' => 'Cleric',
         '3' => 'Paladin',
         '4' => 'Ranger',
         '5' => 'Shadowknight',
         '6' => 'Druid',
         '7' => 'Monk',
         '8' => 'Bard',
         '9' => 'Rogue',
         '10' => 'Shaman',
         '11' => 'Necromancer',
         '12' => 'Wizard',
         '13' => 'Magician',
         '14' => 'Enchanter',
         '15' => 'Beastlord', 
      ];
   
      return $classList[$classId] ?: null;
   }
   
   public function convertSkill($skillId)
   {
      $skillList = [
         '0'  => '1HBlunt',
         '1'  => '1HSlashing',
         '2'  => '2HBlunt',
         '3'  => '2HSlashing',
         '4'  => 'Abjuration',
         '5'  => 'Alteration',
         '6'  => 'ApplyPoison',
         '7'  => 'Archery',
         '8'  => 'Backstab',
         '9'  => 'BindWound',
         '10' => 'Bash',
         '11' => 'Block',
         '12' => 'BrassInstruments',
         '13' => 'Channeling',
         '14' => 'Conjuration',
         '15' => 'Defense',
         '16' => 'Disarm',
         '17' => 'DisarmTraps',
         '18' => 'Divination',
         '19' => 'Dodge',
         '20' => 'DoubleAttack',
         '21' => 'DragonPunch/TailRake',
         '22' => 'DualWield',
         '23' => 'EagleStrike',
         '24' => 'Evocation',
         '25' => 'FeignDeath',
         '26' => 'FlyingKick',
         '27' => 'Forage',
         '28' => 'HandtoHand',
         '29' => 'Hide',
         '30' => 'Kick',
         '31' => 'Meditate',
         '32' => 'Mend',
         '33' => 'Offense',
         '34' => 'Parry',
         '35' => 'PickLock',
         '36' => '_1HPiercing',
         '37' => 'Riposte',
         '38' => 'RoundKick',
         '39' => 'SafeFall',
         '40' => 'SenseHeading',
         '41' => 'Singing',
         '42' => 'Sneak',
         '43' => 'SpecializeAbjure',
         '44' => 'SpecializeAlteration',
         '45' => 'SpecializeConjuration',
         '46' => 'SpecializeDivination',
         '47' => 'SpecializeEvocation',
         '48' => 'PickPockets',
         '49' => 'StringedInstruments',
         '50' => 'Swimming',
         '51' => 'Throwing',
         '52' => 'TigerClaw',
         '53' => 'Tracking',
         '54' => 'WindInstruments',
         '55' => 'Fishing',
         '56' => 'MakePoison',
         '57' => 'Tinkering',
         '58' => 'Research',
         '59' => 'Alchemy',
         '60' => 'Baking',
         '61' => 'Tailoring',
         '62' => 'SenseTraps',
         '63' => 'Blacksmithing',
         '64' => 'Fletching',
         '65' => 'Brewing',
         '66' => 'AlcoholTolerance',
         '67' => 'Begging',
         '68' => 'JewelryMaking',
         '69' => 'Pottery',
         '70' => 'PercussionInstruments',
         '71' => 'Intimidation',
         '72' => 'Berserking',
         '73' => 'Taunt',
      ];
   
      return $skillList[$skillId] ?: null;
   }
   
   public function convertSpellTargetType($spellTargetTypeId)
   {
      $spellTargetTypeList = [
         '1'  => 'TargetOptional',
         '2'  => 'GroupV1',
         '3'  => 'GroupTeleport',
         '4'  => 'PBAE',
         '5'  => 'Single',
         '6'  => 'Self',
         '8'  => 'TargetAE',
         '9'  => 'Animal',
         '10' => 'Undead',
         '11' => 'Summoned',
         '13' => 'Tap',
         '14' => 'Pet',
         '15' => 'Corpse',
         '16' => 'Plant',
         '17' => 'UberGiant',
         '18' => 'UberDragon',
         '20' => 'TargetAETap',
         '24' => 'UndeadAE',
         '25' => 'SummonedAE',
         '40' => 'BardAE',
         '41' => 'GroupV2',
         '43' => 'ProjectIllusion',
      ];
   
      return $spellTargetTypeList[$spellTargetTypeId] ?: null;
   }

   public function convertSpellType($spellTypeMask)
   {
      $types = [];

      $spellTypeList = [
         '0'  => 'Nuke',
         '1'  => 'Heal',
         '2'  => 'Root',
         '3'  => 'Buff',
         '4'  => 'Escape',
         '5'  => 'Pet',
         '6'  => 'Lifetap',
         '7'  => 'Snare',
         '8'  => 'DOT',
         '9'  => 'Dispel',
         '10' => 'InCombatBuff',
         '11' => 'Mez',
         '12' => 'Charm',
         '13' => 'Slow',
         '14' => 'Debuff',
         '15' => 'Cure',
         '16' => 'Resurrect',
      ];

      $bitArray = array_reverse(str_split(base_convert($spellTypeMask,10,2)));

      foreach ($bitArray as $pos => $value) {
         if ($value) { $types[] = $spellTypeList[$pos]; }
      }
   
      return (($types) ? implode('/',$types) : 'None');
   }

   public function convertBlindnessStackingValue($effectValue)
   {
      $valueList = [
         '-1' => 'Low',
         '1'  => 'Normal',
         '2'  => 'Medium',
         '3'  => 'High',
      ];

      return $valueList[$effectValue] ?: null;
   }
   
   public function convertBuffDuration($casterLevel, $formula, $duration) 
   {
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
   
      return $return;
   }
   
   public function convertResistType($resistTypeId)
   {
      $resistTypeList = [
         '0' => 'None',
         '1' => 'Magic',
         '2' => 'Fire',
         '3' => 'Cold',
         '4' => 'Poison',
         '5' => 'Disease',
      ];
   
      return $resistTypeList[$resistTypeId] ?: null;
   }
   
   public function convertEmoteType($emoteTypeId)
   {
      $emoteTypeList = [
         '0' => 'say',
         '1' => 'emote',
         '2' => 'shout',
         '3' => 'message',
      ];
   
      return $emoteTypeList[$emoteTypeId] ?: 'say';
   }

   public function convertBindTypeById($bindId)
   {
      $bindTypeList = [
         '1' => 'Primary',
         '2' => 'Secondary',
         '3' => 'Tertiary',
      ];
   
      return $bindTypeList[$bindId] ?: $bindTypeList['1'];
   }
   
   public function convertNpcEvent($npcEventTypeId)
   {
      $npcEventTypeList = [
         '0' => 'LeaveCombat',
         '1' => 'EnterCombat',
         '2' => 'OnDeath',
         '3' => 'AfterDeath',
         '4' => 'Hailed',
         '5' => 'KilledPC',
         '6' => 'KilledNPC',
         '7' => 'OnSpawn',
         '8' => 'OnDespawn',
         '9' => 'Killed',
      ];
 
      return $npcEventTypeList[$npcEventTypeId] ?: null;
   }    
}