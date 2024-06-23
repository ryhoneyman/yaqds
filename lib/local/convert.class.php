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
         '0' => 'Any',
         '1' => 'Classic',
         '2' => 'Kunark',
         '3' => 'Velious',
         '4' => 'Luclin',
         '5' => 'Planes',
         '6' => 'PostPlanes',
         '7' => 'Disabled',
         '8' => 'Disabled',
      ];
   
      $minorList = [
         ''  => '',
         '0' => '',
         '3' => 'II',
         '6' => 'III',
         '9' => 'IV',
      ];
   
      list($major,$minor) = explode('.',$expansionNumber);
   
      return sprintf("%s%s",$expansionList[$major] ?: 'Unknown',$minorList[$minor] ?: '');
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
            'id'            => $effectId,
            'base'          => $effectBase,
            'limit'         => $effectLimit,
            'max'           => $effectMax,
            'formula'       => $effectFormula,
            'effect'        => $this->convertSpellEffect($effectId,'key'),
            'effectDisplay' => $this->convertSpellEffect($effectId,'display'),
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
            'key'  => 'SE_CurrentHP',
            'display' => ['format' => 1, 'label' => 'Hitpoints', 'allowDuration' => true],
         ],
         '1' => [
            'key'  => 'SE_ArmorClass',
            'display' => ['format' => 2, 'label' => 'AC'],
         ],
         '2' => [
            'key'  => 'SE_ATK',
            'display' => ['format' => 1, 'label' => 'Attack'],
         ],
         '3' => [
            'key'  => 'SE_MovementSpeed',
            'display' => ['format' => 1, 'label' => 'Movement Speed'],
         ],
         '4' => [
            'key'  => 'SE_STR',
            'display' => ['format' => 1, 'label' => 'Strength'],
         ],
         '5' => [
            'key'  => 'SE_DEX',
            'display' => ['format' => 1, 'label' => 'Dexerity'],
         ],
         '6' => [
            'key'  => 'SE_AGI',
            'display' => ['format' => 1, 'label' => 'Agility'],
         ],
         '7' => [
            'key'  => 'SE_STA',
            'display' => ['format' => 1, 'label' => 'Stamina'],
         ],
         '8' => [
            'key'  => 'SE_INT',
            'display' => ['format' => 1, 'label' => 'Intelligence'],
         ],
         '9' => [
            'key'  => 'SE_WIS',
            'display' => ['format' => 1, 'label' => 'Wisdom'],
         ],
         '10' => [
            'key'  => 'SE_CHA',
            'display' => ['format' => 1, 'label' => 'Charisma'],
         ],
         '11' => [
            'key'  => 'SE_AttackSpeed',
            'display' => ['format' => 1, 'label' => 'Attack Speed'],
         ],
         '12' => [
            'key'  => 'SE_Invisibility',
            'display' => ['format' => 0, 'label' => 'Invisibility'],
         ],
         '13' => [
            'key'     => 'SE_SeeInvis',
            'display' => ['format' => 0, 'label' => 'See Invisibile'],
         ],
         '14' => [
            'key'     => 'SE_WaterBreathing',
            'display' => ['format' => 0, 'label' => 'Water Breathing'],
         ],
         '15' => [
            'key'  => 'SE_CurrentMana',
            'display' => ['format' => 1, 'label' => 'Mana', 'allowDuration' => true],
         ],
         '18' => [
            'key'  => 'SE_Lull',
            'display' => ['format' => 0, 'label' => 'Pacify'],
         ],
         '19' => [
            'key'  => 'SE_AddFaction',
            'text' => 'foo'
         ],
         '20' => [
            'key'  => 'SE_Blind',
            'text' => 'foo'
         ],
         '21' => [
            'key'  => 'SE_Stun',
            'text' => 'foo'
         ],
         '22' => [
            'key'  => 'SE_Charm',
            'text' => 'foo'
         ],
         '23' => [
            'key'  => 'SE_Fear',
            'text' => 'foo'
         ],
         '24' => [
            'key'  => 'SE_Stamina',
            'text' => 'foo'
         ],
         '25' => [
            'key'  => 'SE_BindAffinity',
            'text' => 'foo'
         ],
         '26' => [
            'key'  => 'SE_Gate',
            'text' => 'foo'
         ],
         '27' => [
            'key'  => 'SE_CancelMagic',
            'text' => 'foo'
         ],
         '28' => [
            'key'  => 'SE_InvisVsUndead',
            'display' => ['format' => 0, 'label' => 'Invisibility versus Undead'],
         ],
         '29' => [
            'key'     => 'SE_InvisVersusAnimals',
            'display' => ['format' => 0, 'label' => 'Invisibility versus Animals'],
         ],
         '30' => [
            'key'     => 'SE_ChangeFrenzyRadius',
            'display' => ['format' => 6, 'label' => 'Aggro Radius'],
         ],
         '31' => [
            'key'     => 'SE_Mez',
            'display' => ['format' => 3, 'label' => 'Mezmerize'],
         ],
         '32' => [
            'key'     => 'SE_SummonItem',
            'display' => [
               'format' => 'Summon: {{itemName}}', 
               'values' => [
                  'itemName' => 'data^getItemInfoById^{{effect:base}}^Name',
               ],
            ],
         ],
         '33' => [
            'key'  => 'SE_SummonPet',
            'text' => 'foo'
         ],
         '35' => [
            'key'     => 'SE_DiseaseCounter',
            'display' => ['format' => 1, 'label' => 'Disease Counter'],
         ],
         '36' => [
            'key'  => 'SE_PoisonCounter',
            'text' => 'foo'
         ],
         '40' => [
            'key'  => 'SE_DivineAura',
            'text' => 'foo'
         ],
         '41' => [
            'key'  => 'SE_Destroy',
            'display' => ['format' => 'Destroy {{spell:targetTypeName}} up to L51'],
         ],
         '42' => [
            'key'  => 'SE_ShadowStep',
            'text' => 'foo'
         ],
         '43' => [
            'key'  => 'SE_Berserk',
            'text' => 'foo'
         ],
         '44' => [
            'key'  => 'SE_Lycanthropy',
            'text' => 'foo'
         ],
         '45' => [
            'key'  => 'SE_Vampirism',
            'text' => 'foo'
         ],
         '46' => [
            'key'  => 'SE_ResistFire',
            'text' => 'foo'
         ],
         '47' => [
            'key'  => 'SE_ResistCold',
            'text' => 'foo'
         ],
         '48' => [
            'key'  => 'SE_ResistPoison',
            'text' => 'foo'
         ],
         '49' => [
            'key'  => 'SE_ResistDisease',
            'text' => 'foo'
         ],
         '50' => [
            'key'  => 'SE_ResistMagic',
            'text' => 'foo'
         ],
         '52' => [
            'key'     => 'SE_SenseDead',
            'display' => ['format' => 0, 'label' => 'Sense Undead'],
         ],
         '53' => [
            'key'     => 'SE_SenseSummoned',
            'display' => ['format' => 0, 'label' => 'Sense Summoned'],
         ],
         '54' => [
            'key'     => 'SE_SenseAnimals',
            'display' => ['format' => 0, 'label' => 'Sense Animals'],
         ],
         '55' => [
            'key'  => 'SE_Rune',
            'text' => 'foo'
         ],
         '56' => [
            'key'     => 'SE_TrueNorth',
            'display' => ['format' => 0, 'label' => 'True North'],
         ],
         '57' => [
            'key'     => 'SE_Levitate',
            'display' => ['format' => 0, 'label' => 'Levitate'],
         ],
         '58' => [
            'key'  => 'SE_Illusion',
            'display' => [
               'format' => 'Illusion: {{raceName}}', 
               'values' => [
                  'raceName' => 'conv^convertRace^{{effect:base}}',
               ],
            ],
         ],
         '59' => [
            'key'  => 'SE_DamageShield',
            'text' => 'foo'
         ],
         '61' => [
            'key'     => 'SE_Identify',
            'display' => ['format' => 0, 'label' => 'Identify'],
         ],
         '63' => [
            'key'     => 'SE_WipeHateList',
            'display' => ['format' => 4, 'label' => 'Memblur'],
         ],
         '64' => [
            'key'     => 'SE_SpinTarget',
            'display' => ['format' => 0, 'label' => 'Spin Stun'],
         ],
         '65' => [
            'key'     => 'SE_Infravision',
            'display' => ['format' => 0, 'label' => 'Infravision'],
         ],
         '66' => [
            'key'     => 'SE_Ultravision',
            'display' => ['format' => 0, 'label' => 'Ultravision'],
         ],
         '67' => [
            'key'  => 'SE_EyeOfZoom',
            'text' => 'foo'
         ],
         '68' => [
            'key'     => 'SE_ReclaimPet',
            'display' => ['format' => 0, 'label' => 'Reclaim Pet'],
         ],
         '69' => [
            'key'     => 'SE_TotalHP',
            'display' => ['format' => 1, 'label' => 'Maximum Hitpoints'],
         ],
         '71' => [
            'key'  => 'SE_NecPet',
            'text' => 'foo'
         ],
         '73' => [
            'key'     => 'SE_Bindsight',
            'display' => ['format' => 0, 'label' => 'Bind Sight'],
         ],
         '74' => [
            'key'     => 'SE_FeignDeath',
            'display' => ['format' => 0, 'label' => 'Feign Death'],
         ],
         '75' => [
            'key'     => 'SE_VoiceGraft',
            'display' => ['format' => 0, 'label' => 'Voice Graft'],
         ],
         '76' => [
            'key'     => 'SE_Sentinel',
            'display' => ['format' => 0, 'label' => 'Sentinel'],
         ],
         '77' => [
            'key'     => 'SE_LocateCorpse',
            'display' => ['format' => 0, 'label' => 'Locate Corpse'],
         ],
         '78' => [
            'key'  => 'SE_AbsorbMagicAttack',
            'text' => 'foo'
         ],
         '79' => [
            'key'     => 'SE_CurrentHPOnce',
            'display' => ['format' => 1, 'label' => 'Hitpoints'],
         ],
         '81' => [
            'key'     => 'SE_Revive',
            'display' => ['format' => 'Resurrect and restore {{effect:base}}% experience'],
         ],
         '82' => [
            'key'     => 'SE_SummonPC',
            'display' => ['format' => 0, 'label' => 'Summon Player'],
         ],
         '83' => [
            'key'     => 'SE_Teleport',
            'display' => ['format' => 5, 'label' => 'Teleport'],
         ],
         '84' => [
            'key'  => 'SE_TossUp',
            'text' => 'foo'
         ],
         '85' => [
            'key'  => 'SE_WeaponProc',
            'text' => 'foo'
         ],
         '86' => [
            'key'     => 'SE_Harmony',
            'display' => ['format' => 6, 'label' => 'Assist Radius'],
         ],
         '87' => [
            'key'  => 'SE_MagnifyVision',
            'text' => 'foo'
         ],
         '88' => [
            'key'  => 'SE_Succor',
            'text' => 'foo'
         ],
         '89' => [
            'key'  => 'SE_ModelSize',
            'text' => 'foo'
         ],
         '91' => [
            'key'     => 'SE_SummonCorpse',
            'display' => ['format' => 'Summon Corpse up to L{{effect:base}}'],
         ],
         '92' => [
            'key'  => 'SE_InstantHate',
            'text' => 'foo'
         ],
         '93' => [
            'key'     => 'SE_StopRain',
            'display' => ['format' => 0, 'label' => 'Stop Rain'],
         ],
         '94' => [
            'key'  => 'SE_NegateIfCombat',
            'text' => 'foo'
         ],
         '95' => [
            'key'     => 'SE_Sacrifice',
            'display' => [
               'format' => 'Sacrifice Player between L{{sacrificeMinLevel}} and L{{sacrificeMaxLevel}}', 
               'values' => [
                  'sacrificeMinLevel' => 'data^getRuleInfoByName^Spells:SacrificeMinLevel^rule_value',
                  'sacrificeMaxLevel' => 'data^getRuleInfoByName^Spells:SacrificeMaxLevel^rule_value',
               ],
            ],
         ],
         '96' => [
            'key'  => 'SE_Silence',
            'text' => 'foo'
         ],
         '97' => [
            'key'  => 'SE_ManaPool',
            'display' => ['format' => 1, 'label' => 'Maximum Mana'],
         ],
         '98' => [
            'key'  => 'SE_AttackSpeed2',
            'text' => 'foo'
         ],
         '99' => [
            'key'     => 'SE_Root',
            'display' => ['format' => 0, 'label' => 'Root'],
         ],
         '100' => [
            'key'  => 'SE_HealOverTime',
            'text' => 'foo'
         ],
         '101' => [
            'key'  => 'SE_CompleteHeal',
            'text' => 'foo'
         ],
         '102' => [
            'key'  => 'SE_Fearless',
            'text' => 'foo'
         ],
         '103' => [
            'key'  => 'SE_CallPet',
            'text' => 'foo'
         ],
         '104' => [
            'key'  => 'SE_Translocate',
            'display' => ['format' => 5, 'label' => 'Translocate'],
         ],
         '105' => [
            'key'  => 'SE_AntiGate',
            'text' => 'foo'
         ],
         '106' => [
            'key'  => 'SE_SummonBSTPet',
            'text' => 'foo'
         ],
         '107' => [
            'key'  => 'SE_AlterNPCLevel',
            'text' => 'foo'
         ],
         '108' => [
            'key'  => 'SE_Familiar',
            'text' => 'foo'
         ],
         '109' => [
            'key'  => 'SE_SummonItemIntoBag',
            'text' => 'foo'
         ],
         '110' => [
            'key'  => 'SE_IncreaseArchery',
            'text' => 'foo'
         ],
         '111' => [
            'key'  => 'SE_ResistAll',
            'text' => 'foo'
         ],
         '112' => [
            'key'  => 'SE_CastingLevel',
            'text' => 'foo'
         ],
         '113' => [
            'key'  => 'SE_SummonHorse',
            'text' => 'foo'
         ],
         '114' => [
            'key'  => 'SE_ChangeAggro',
            'text' => 'foo'
         ],
         '115' => [
            'key'  => 'SE_Hunger',
            'text' => 'foo'
         ],
         '116' => [
            'key'  => 'SE_CurseCounter',
            'text' => 'foo'
         ],
         '117' => [
            'key'  => 'SE_MagicWeapon',
            'text' => 'foo'
         ],
         '118' => [
            'key'  => 'SE_Amplification',
            'text' => 'foo'
         ],
         '119' => [
            'key'  => 'SE_AttackSpeed3',
            'text' => 'foo'
         ],
         '120' => [
            'key'  => 'SE_HealRate',
            'text' => 'foo'
         ],
         '121' => [
            'key'  => 'SE_ReverseDS',
            'text' => 'foo'
         ],
         '123' => [
            'key'  => 'SE_Screech',
            'text' => 'foo'
         ],
         '124' => [
            'key'  => 'SE_ImprovedDamage',
            'text' => 'foo'
         ],
         '125' => [
            'key'  => 'SE_ImprovedHeal',
            'text' => 'foo'
         ],
         '126' => [
            'key'  => 'SE_SpellResistReduction',
            'text' => 'foo'
         ],
         '127' => [
            'key'  => 'SE_IncreaseSpellHaste',
            'text' => 'foo'
         ],
         '128' => [
            'key'  => 'SE_IncreaseSpellDuration',
            'text' => 'foo'
         ],
         '129' => [
            'key'  => 'SE_IncreaseRange',
            'text' => 'foo'
         ],
         '130' => [
            'key'  => 'SE_SpellHateMod',
            'text' => 'foo'
         ],
         '131' => [
            'key'  => 'SE_ReduceReagentCost',
            'text' => 'foo'
         ],
         '132' => [
            'key'  => 'SE_ReduceManaCost',
            'text' => 'foo'
         ],
         '133' => [
            'key'  => 'SE_RFcStunTimeMod',
            'text' => 'foo'
         ],
         '145' => [
            'key'  => 'SE_Teleport2',
            'text' => 'foo'
         ],
         '147' => [
            'key'  => 'SE_PercentHeal',
            'text' => 'foo'
         ],
         '148' => [
            'key'  => 'SE_StackingCommandBlock',
            'text' => 'foo'
         ],
         '149' => [
            'key'  => 'SE_StackingCommandOverwrite',
            'text' => 'foo'
         ],
         '150' => [
            'key'  => 'SE_DeathSave',
            'text' => 'foo'
         ],
         '151' => [
            'key'  => 'SE_SuspendPet',
            'text' => 'foo'
         ],
         '152' => [
            'key'  => 'SE_TemporaryPets',
            'text' => 'foo'
         ],
         '153' => [
            'key'  => 'SE_BalanceHP',
            'text' => 'foo'
         ],
         '154' => [
            'key'  => 'SE_DispelDetrimental',
            'text' => 'foo'
         ],
         '155' => [
            'key'  => 'SE_SpellCritDmgIncrease',
            'text' => 'foo'
         ],
         '156' => [
            'key'  => 'SE_IllusionCopy',
            'text' => 'foo'
         ],
         '157' => [
            'key'  => 'SE_SpellDamageShield',
            'text' => 'foo'
         ],
         '158' => [
            'key'  => 'SE_Reflect',
            'text' => 'foo'
         ],
         '159' => [
            'key'  => 'SE_AllStats',
            'text' => 'foo'
         ],
         '161' => [
            'key'  => 'SE_MitigateSpellDamage',
            'text' => 'foo'
         ],
         '162' => [
            'key'  => 'SE_MitigateMeleeDamage',
            'text' => 'foo'
         ],
         '163' => [
            'key'  => 'SE_NegateAttacks',
            'text' => 'foo'
         ],
         '167' => [
            'key'  => 'SE_PetPowerIncrease',
            'text' => 'foo'
         ],
         '168' => [
            'key'  => 'SE_MeleeMitigation',
            'text' => 'foo'
         ],
         '169' => [
            'key'  => 'SE_CriticalHitChance',
            'text' => 'foo'
         ],
         '170' => [
            'key'  => 'SE_SpellCritChance',
            'text' => 'foo'
         ],
         '171' => [
            'key'  => 'SE_CripplingBlowChance',
            'text' => 'foo'
         ],
         '172' => [
            'key'  => 'SE_AvoidMeleeChance',
            'text' => 'foo'
         ],
         '173' => [
            'key'  => 'SE_RiposteChance',
            'text' => 'foo'
         ],
         '174' => [
            'key'  => 'SE_DodgeChance',
            'text' => 'foo'
         ],
         '175' => [
            'key'  => 'SE_ParryChance',
            'text' => 'foo'
         ],
         '176' => [
            'key'  => 'SE_DualWieldChance',
            'text' => 'foo'
         ],
         '177' => [
            'key'  => 'SE_DoubleAttackChance',
            'text' => 'foo'
         ],
         '178' => [
            'key'  => 'SE_MeleeLifetap',
            'text' => 'foo'
         ],
         '179' => [
            'key'  => 'SE_AllInstrumentMod',
            'text' => 'foo'
         ],
         '180' => [
            'key'  => 'SE_ResistSpellChance',
            'text' => 'foo'
         ],
         '181' => [
            'key'  => 'SE_ResistFearChance',
            'text' => 'foo'
         ],
         '182' => [
            'key'  => 'SE_HundredHands',
            'text' => 'foo'
         ],
         '183' => [
            'key'  => 'SE_MeleeSkillCheck',
            'text' => 'foo'
         ],
         '184' => [
            'key'  => 'SE_HitChance',
            'text' => 'foo'
         ],
         '185' => [
            'key'  => 'SE_DamageModifier',
            'text' => 'foo'
         ],
         '186' => [
            'key'  => 'SE_MinDamageModifier',
            'text' => 'foo'
         ],
         '254' => [
            'key'  => 'SE_Blank',
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