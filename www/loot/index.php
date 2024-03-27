<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'debugLevel'     => 0,
   'errorReporting' => false,
   'sessionStart'   => true,
   'memoryLimit'    => null,
   'sendHeaders'    => true,
   'dbConfigDir'    => APP_CONFIGDIR,
   'fileDefine'     => APP_CONFIGDIR.'/defines.json',
   'database'       => true,
   'input'          => false,
   'html'           => false,
   'adminlte'       => true,
   'data'           => APP_CONFIGDIR.'/global.json',
));

include 'ui/header.php';

$currentExpansion = $main->data->currentExpansion();

$global = array('currentExpansion' => $currentExpansion);

$lootTables = array(
   'Lady Vox' => array(
      'tableEntries' => array(    
         array(    
            'dropLimit' => 1,
            'minDrop' => 1,
            'probability' => 40,
            'multiplier' => 6,
            'multiplier_min' => 3,
            'dropEntries' => array(
               array(
                  'item_name'     => "White Dragon Hide",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "Torbin's Mystical Eyepatch",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "Falchion of the Mistwalker",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                'item_name'     => "Runed Bolster Belt",
                'multiplier'    => 1,
                'chance'        => 10,
                'min_expansion' => 0,
                'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "White Dragon Scales",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "Scimitar of the Mistwalker",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "White Dragon Tooth",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "Dragon Bone Bracelet",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "McVaxius' Horn of War",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "Crystalline Spear",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "Warhammer of Divine Grace",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "Karvruul's Mystic Pouch",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
               array(
                  'item_name'     => "Torn, Frost covered book",
                  'multiplier'    => 1,
                  'chance'        => 10,
                  'min_expansion' => 0,
                  'max_expansion' => 0,
               ),
            ),
         ),
      ), 
   ),
);

$npc     = 'Lady Vox';
$done    = false;
$counter = 0;
$stats   = array();

while (!$done) {
   $counter++;
   $lootResults = checkLoot($global,$lootTables[$npc]);
   
   $stats['kills']++;
   
   foreach ($lootResults as $tableId => $tableLoot) {
      $stats['table'][$tableId]['total'] += $tableLoot['total'];
      foreach ($tableLoot['item'] as $itemName => $itemCount) { 
         $stats['table'][$tableId]['item'][$itemName] += $itemCount;
      }
   }
   
   if ($counter >= 10000) { $done = true; }
}

printf("<pre class='text-white'>\n");
printf("\nChecking loot results for %s after %d kills\n",$npc,$stats['kills']);

foreach ($stats['table'] as $tableId => $tableLoot) {
   printf("Table %s\n",$tableId);
   foreach ($tableLoot['item'] as $itemName => $itemCount) { 
      printf("  %3d%% %s\n",$itemCount/$tableLoot['total']*100,$itemName);
   }
}

printf("\n");
printf("</pre>\n");

include 'ui/footer.php';

?>
<?php

function checkLoot($global, $npcLootTable)
{
   $lootResults = array();

   if (!$npcLootTable || !$npcLootTable['tableEntries']) { return null; }

   $tableEntries = $npcLootTable['tableEntries'];

   logMesg("Found ".count($tableEntries)." table entries");

   foreach ($tableEntries as $tableId => $lootTableEntry) {
      $npcDrops = calculateLootTable($global,$lootTableEntry);

      $totalCount = 0;
     
      foreach ($npcDrops as $itemName => $itemList) { 
         $itemCount = is_array($itemList) ? count($itemList) : $itemList;
         $totalCount += $itemCount;
         $lootResults[$tableId]['item'][$itemName] = $itemCount;
      }
     
      if (!array_key_exists('total',$lootResults[$tableId])) { $lootResults[$tableId]['total'] = 0; }

      $lootResults[$tableId]['total'] += $totalCount;
   }

   return $lootResults;
}

function calculateLootTable($global, $lootTableEntry) {
   $lootDrops = array();

   if (!$lootTableEntry) { return false; }

   $multiplierCount    = 0;
   $tableMultiplier    = $lootTableEntry['multiplier'];
   $tableMultiplierMin = $lootTableEntry['multiplier_min'];

   for ($multiplier = 1; $multiplier <= $tableMultiplier; $multiplier++) {
      $tableChance = $lootTableEntry['probability'];
      $dropChance  = 0;
      $dropForced  = false;

      if ($tableChance > 0 && $tableChance < 100 && $multiplierCount >= $tableMultiplierMin) {
         $dropChance = randFloat(0,100);
      }
      else if ($multiplierCount < $tableMultiplierMin) {
         $dropChance = 0;
         $dropForced = true;
      }

      $doDrop = ($tableChance != 0 && ($tableChance == 100 || $dropChance <= $tableChance)) ? true : false;

      logMesg("*** Checking loot table entry at multiplier($multiplier) with probability($tableChance): dropForced(".json_encode($dropForced).") dropChance($dropChance) doDrop(".json_encode($doDrop).")");

      if ($dropForced) { logMesg("*** Have not met the minimum multiplier requirements, forcing loot drop"); }

      if ($doDrop) {
         logMesg("*** Beginning loot drop calculations");
         $tableDrops = calculateLootDrop($global,$multiplier,$lootTableEntry);
         if ($tableDrops) { $lootDrops = array_merge_recursive($lootDrops,$tableDrops); }
      }

      $multiplierCount++;
   }

   return $lootDrops;
}

function calculateLootDrop($global, $counter, $lootTableEntry)
{
   $lootDrops        = array();
   $currentExpansion = $global['currentExpansion'];
   $counter          = sprintf("%2d",$counter);

   if (!$lootTableEntry) { return false; }

   $dropLimit   = $lootTableEntry['dropLimit'];
   $minDrop     = $lootTableEntry['minDrop'];
   $lootEntries = $lootTableEntry['dropEntries'];
   $entryCount  = count($lootEntries);

   if (count($lootEntries) == 0) { return false; } 

   if ($dropLimit == 0 && $minDrop == 0) {
      logMesg("$counter: *** No droplimit or mindrop was specified");
 
      foreach ($lootEntries as $lootEntry) {
        $itemName       = $lootEntry['item_name'];
        $itemChance     = $lootEntry['chance'];
        $itemMultiplier = $lootEntry['multiplier'];

         if (!validExpansion($currentExpansion,$lootEntry['min_expansion'],$lootEntry['max_expansion'])) { 
            logMesg("$counter: !!! Expansion not valid for $itemName");
            continue; 
         }

         for ($multiplier = 0; $multiplier < $itemMultiplier; ++$multiplier) {
            $roll = randFloat(0,100);

            logMesg("$counter: @@@ Rolling for $itemName ($multiplier): $roll (need $itemChance)");

            if ($roll <= $itemChance) {  
                logMesg("$counter: $$$ Loot drop assigned: $itemName");
                $lootDrops[$itemName]++; 
            }
         }
      }

      return $lootDrops;
   }

   if ($entryCount > 100 && $dropLimit == 0) { 
      $dropLimit = 10; 
      logMesg("$counter: *** Loot entries exceeded limit of 100 with no drop limit, force drop limit to $dropLimit");
   }

   if ($dropLimit < $minDrop) { 
      logMesg("$counter: *** Drop limit ($dropLimit) was less than minimum drops ($minDrop), force drop limit to $minDrop");
      $dropLimit = $minDrop;
   }

   $rollTotalActual = 0;
   $rollTotal       = 0;
   $activeItemList  = false;

   logMesg("$counter: ### Calculating");

   foreach ($lootEntries as $lootEntry) {
      $itemName       = $lootEntry['item_name'];
      $itemChance     = $lootEntry['chance'];
      $itemMultiplier = $lootEntry['multiplier'];

      if (!validExpansion($currentExpansion,$lootEntry['min_expansion'],$lootEntry['max_expansion'])) { 
         logMesg("$counter: !!! Expansion not valid for $itemName");
         continue; 
      }

      //logMesg("$counter: *** $itemName adding $itemChance to total");

      $rollTotalActual += $itemChance;
      $activeItemList = true;
   }

   logMesg("$counter: *** Roll total for table = $rollTotalActual");
   $rollTotal = max($rollTotalActual,100);

   if (!$activeItemList) { return null; }

   logMesg("$counter: ### Phase 1");

   for ($drop = 0; $drop < $minDrop; $drop++) {
      $roll = randFloat(0,$rollTotalActual);

      logMesg("$counter: @@@ Rolling for drop($drop) minDrop($minDrop): $roll out of $rollTotalActual");

      foreach ($lootEntries as $lootEntry) {
         $itemName       = $lootEntry['item_name'];
         $itemChance     = $lootEntry['chance'];
         $itemMultiplier = $lootEntry['multiplier'];

         if (!validExpansion($currentExpansion,$lootEntry['min_expansion'],$lootEntry['max_expansion'])) { 
            logMesg("$counter: !!! Expansion not valid for $itemName");
            continue; 
         }

         logMesg("$counter: *** Checking $itemName: $roll < $itemChance");

         if ($roll < $itemChance) {
            logMesg("$counter: $$$ Loot drop assigned: $itemName");

            if (!array_key_exists($itemName,$lootDrops)) { $lootDrops[$itemName] = 0; }
            $lootDrops[$itemName]++; 

            $itemMultiplier = max($itemMultiplier,1);

            if ($itemMultiplier > 1) { logMesg("$counter: *** Checking multiplier on $itemName ($itemMultiplier)"); }

            for ($multiplier = 1; $multiplier < $itemMultiplier; $multiplier++) {
               $rollMulti = randFloat(0,100);
               logMesg("$counter: @@@ Rolling for multiplier($multiplier) on $itemName: $rollMulti");

               if ($rollMulti <= $itemChance) { 
                  logMesg("$counter: $$$ Loot drop assigned: $itemName");
                  $lootDrops[$itemName]++; 
               }
            }

            break;
         }
         else { $roll -= $itemChance; }
      }
   }

   logMesg("$counter: ### Phase 2");

   if (($dropLimit - $minDrop) == 1) {
      logMesg("$counter: *** Difference between dropLimit($dropLimit) and minDrop($minDrop) is 1");

      $roll = randFloat(0,$rollTotal);

      logMesg("$counter: @@@ Rolling: $roll");

      foreach ($lootEntries as $lootEntry) {
         $itemName       = $lootEntry['item_name'];
         $itemChance     = $lootEntry['chance'];
         $itemMultiplier = $lootEntry['multiplier'];

         if (!validExpansion($currentExpansion,$lootEntry['min_expansion'],$lootEntry['max_expansion'])) { 
            logMesg("$counter: !!! Expansion not valid for $itemName");
            continue; 
         }

         if ($roll < $itemChance) {
            logMesg("$counter: $$$ Loot drop assigned: $itemName");
            $lootDrops[$itemName]++; 

            $itemMultiplier = max($itemMultiplier,1);

            for ($multiplier = 1; $multiplier < $itemMultiplier; $multiplier++) {
               $rollMulti = randFloat(0,100);
               logMesg("$counter: @@@ Rolling for multiplier($multiplier) on $itemName: $rollMulti");

               if ($rollMulti <= $itemChance) { 
                  logMesg("$counter: $$$ Loot drop assigned: $itemName");
                  $lootDrops[$itemName]++; 
               }
            }

            break;
         }
         else { $roll -= $itemChance; }
      }
   }
   else if ($dropLimit > $minDrop) {
      logMesg("$counter: *** dropLimit($dropLimit) is greater than minDrop($minDrop)");

      $dropCount    = $minDrop;
      $itemPosition = array_rand($lootEntries);
      
      for ($loops = 0; $loops < $entryCount; $loops++) {
         if ($dropCount > $dropLimit) { 
            logMesg("$counter: *** dropCount($dropCount) is greater than dropLimit($dropLimit)");
            break; 
         }

         $lootEntry      = $lootEntries[$itemPosition];
         $itemName       = $lootEntry['item_name'];
         $itemChance     = $lootEntry['chance'];
         $itemMultiplier = $lootEntry['multiplier'];

         if (validExpansion($currentExpansion,$lootEntry['min_expansion'],$lootEntry['max_expansion'])) { 
            $roll = randFloat(0,100);

            logMesg("$counter: @@@ Rolling for entry position($itemPosition): $roll");

            if ($roll > $itemChance) {
               logMesg("$counter: $$$ Loot drop assigned: $itemName");
               $lootDrops[$itemName]++; 

               $itemMultiplier = max($itemMultiplier,1);

               for ($multiplier = 1; $multiplier < $itemMultiplier; ++$multiplier) {
                  $rollMulti = randFloat(0,100);
                  logMesg("$counter: @@@ Rolling for multiplier($multiplier) on $itemName at position($itemPosition): $rollMulti");

                  if ($rollMulti <= $itemChance) { 
                     logMesg("$counter: $$$ Loot drop assigned: $itemName");
                     $lootDrops[$itemName]++; 
                  }
               }

               $dropCount++;
            }
         }

         $itemPosition++;

         if ($itemPosition > $entryCount) { $itemPosition = 0; }
      }
   }

   logMesg("$counter: ### Done");

   return $lootDrops;
}

function validExpansion($currentExpansion, $minExpansion, $maxExpansion)
{
   return (($minExpansion == 0 || ($currentExpansion >= $minExpansion && $currentExpansion < $maxExpansion)) ? true : false);
}

function randFloat($min, $max, $precision = 5)
{
    return sprintf("%.".$precision."f",$min + (lcg_value() * ($max - $min)));
}

function logMesg($message)
{
   //printf("[%s] %s\n",date('Y-m-d H:i:s'),$message);
}
?>