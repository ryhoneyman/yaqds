<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'debugLevel'     => 0,
   'debugType'      => DEBUG_HTML,
   'errorReporting' => false,
   'sessionStart'   => true,
   'memoryLimit'    => null,
   'sendHeaders'    => true,
   'dbConfigDir'    => APP_CONFIGDIR,
   'fileDefine'     => APP_CONFIGDIR.'/defines.json',
   'database'       => true,
   'input'          => true,
   'html'           => true,
   'adminlte'       => true,
   'data'           => APP_CONFIGDIR.'/global.json',
));

$input = $main->obj('input');
$html  = $main->obj('html');
$alte  = $main->obj('adminlte');

$npcHash    = $input->get('npc','alphanumeric');
$analyze    = $input->isDefined('analyze') ? true : false;
$sample     = $input->isDefined('sample') ? true : false;
$iterations = $input->get('iterations','numeric');

if ($iterations <= 0 || $iterations > 10000) { $iterations = 10000; }

if ($sample) { $iterations = 1; }

$npcLootTableList = $main->data->getNpcLootTableList(array('sort' => true));

include 'ui/header.php';

$npcList = array();
foreach ($npcLootTableList['data'] as $index => $data) { $npcList[$data['hash']] = sprintf("%s (%d)",$data['name'],$data['loottable_id']); }

print $alte->displayCard($alte->displayRow(
         $html->startForm().
         "<div class='input-group' style='width:fit-content;'>".    
         $html->select('npc',$npcList,$npcHash).
         $html->submit('analyze','Analyze Chances').
         $html->submit('sample','Simulate Drops').
         "</div>".
         $html->endForm(),
         array('container' => 'col-4')
      ),array('container' => 'col-4'));

$lootTables = array();
$itemLookup = array();

if ($npcHash && $npcLootTableList['lookup'][$npcHash]) {
   $npcIndex         = $npcLootTableList['lookup'][$npcHash];
   $lootTableId      = $npcLootTableList['data'][$npcIndex]['loottable_id'];
   $lootTableEntries = $main->data->getLootTableEntriesById($lootTableId);

   $lootTables['npc_name']     = $npcLootTableList['data'][$npcIndex]['name'];
   $lootTables['tableEntries'] = $lootTableEntries;

   //print "<pre class='text-white'>\n";

   foreach ($lootTableEntries as $index => $entryData) {
      $dropEntries = $main->data->getLootDropEntriesById($entryData['lootdrop_id']);
      $lootTables['tableEntries'][$index]['dropEntries'] = $dropEntries;

      foreach ($dropEntries as $lootEntry) {
         $itemName         = $lootEntry['item_name'];
         $dropMinExpansion = $lootEntry['drop_min_expansion'];
         $dropMaxExpansion = $lootEntry['drop_max_expansion'];
         $itemMinExpansion = $lootEntry['item_min_expansion'];
         $itemMaxExpansion = $lootEntry['item_max_expansion'];  
         $minExpansion     = calculateExpansion('min',$dropMinExpansion,$itemMinExpansion);
         $maxExpansion     = calculateExpansion('max',$dropMaxExpansion,$itemMaxExpansion);

         //printf("%s drops(%s-%s) allowed(%s-%s) using(%s-%s)\n",
         //       $itemName,$dropMinExpansion,$dropMaxExpansion,$itemMinExpansion,$itemMaxExpansion,$minExpansion,$maxExpansion);

         $itemLookup[$index][$itemName] = [
            'min_expansion' => $minExpansion,
            'max_expansion' => $maxExpansion,
         ]; 
      }
   }

   //print "</pre>\n";
}

foreach ($lootTableEntry['dropEntries'] as $lootEntry) {
   $itemName         = $lootEntry['item_name'];
   $dropMinExpansion = $lootEntry['drop_min_expansion'];
   $dropMaxExpansion = $lootEntry['drop_max_expansion'];
   $itemMinExpansion = $lootEntry['item_min_expansion'];
   $itemMaxExpansion = $lootEntry['item_max_expansion'];
   $minExpansion     = min($dropMinExpansion,$itemMinExpansion);
   $maxExpansion     = min($dropMaxExpansion,$itemMaxExpansion);

   $return['table'][$tableId]['item_data'][$itemName] = [
      'min_expansion' => $minExpansion,
      'max_expansion' => $maxExpansion,
   ]; 
}


if ($main->debug->level() >= 8) {
   print "<pre class='text-white'>\n".json_encode($lootTables,JSON_PRETTY_PRINT)."</pre>\n";
}


$npc     = $lootTables['npc_name'];
$done    = false;
$counter = 0;
$stats   = array();

while (!$done) {
   $counter++;
   $lootResults = checkLoot($main,$lootTables);
   
   if (!isset($stats['kills'])) { $stats['kills'] = 0; }

   $stats['kills']++;

   if (isset($lootResults['meta']) && is_array($lootResults['meta'])) {
      foreach ($lootResults['meta'] as $metaKey => $metaCount) {
         if (!isset($stats['meta'][$metaKey])) { $stats['meta'][$metaKey] = 0; }
         $stats['meta'][$metaKey] += $metaCount;
      }
   }
   
   if (isset($lootResults['table']) && is_array($lootResults['table'])) {
      foreach ($lootResults['table'] as $tableId => $tableLoot) {
         if (!isset($stats['table'][$tableId]['total'])) { $stats['table'][$tableId]['total'] = 0; }
         $stats['table'][$tableId]['total'] += $tableLoot['total'];

         if (!isset($tableLoot['item'])) { continue; }

         foreach ($tableLoot['item'] as $itemName => $itemCount) { 
            if (!isset($stats['table'][$tableId]['item'][$itemName])) { $stats['table'][$tableId]['item'][$itemName] = 0; }
            $stats['table'][$tableId]['item'][$itemName] += $itemCount;
         }
      }
   }
   
   if ($counter >= $iterations) { $done = true; }
}

if ($main->debug->level() >= 8) {
   print "<pre class='text-white'>\n"; print json_encode($stats,JSON_PRETTY_PRINT); print "</pre>\n";
}

if ($sample) {
   $statTables = $stats['table'];

   printf("<pre class='text-white'>\n");
   printf("<b class='text-green'>Sample loot results for <u>%s</u>:</b>\n\n",$npc);

   $dropCount = 0;

   foreach ($statTables as $tableId => $tableLootEntry) {
      $lootTableItems = $tableLootEntry['item'] ?: array();

      foreach ($lootTableItems as $itemName => $itemCount) { 
         printf("%s\n",$itemName);
         $dropCount++;
      }
   }

   if ($dropCount == 0) { print "None\n"; }

   printf("\n");
   printf("</pre>\n");
}
else if ($analyze) {
   $totalKills = $stats['kills'];
   $statTables = $stats['table'];

   printf("<pre class='text-white'>\n");
   printf("<b class='text-green'>Checking loot results for <u>%s</u> after %d kills:</b>\n\n",$npc,$totalKills);

   $tableCount = 1;

   foreach ($statTables as $tableId => $tableLootEntry) {
      $lootTableData      = $lootTables['tableEntries'][$tableId];
      $lootTableDropTotal = $tableLootEntry['total'];
      $lootTableItems     = $tableLootEntry['item'] ?: array();

      printf("<b class='text-yellow'>Table #%s (%s) minItems(%d) maxItems(%d) probability(%d) totalDrops(%d)</b>\n",
             $tableCount++,$tableId,$lootTableData['multiplier_min'],$lootTableData['multiplier'],$lootTableData['probability'],$lootTableDropTotal);

      foreach ($lootTableItems as $itemName => $itemCount) { 
         $globalPercent  = ($itemCount/$totalKills) * 100;
         $perKillPercent = (($itemCount >= $totalKills) ? 1 : (($itemCount != $lootTableDropTotal) ? $itemCount/$lootTableDropTotal : $itemCount/$totalKills)) * 100;
         $itemInfo       = $itemLookup[$tableId][$itemName] ?: array();
         $minExpansion   = $itemInfo['min_expansion'];
         $maxExpansion   = $itemInfo['max_expansion'];
         $itemExpansion  = sprintf("%s-%s",$minExpansion,$maxExpansion);

         printf("  %5.1f%% %s dropped(%d) relativeTableChance(%1.1f%%) expansion(%s)\n",$globalPercent,$itemName,$itemCount,$perKillPercent,$itemExpansion);
      }

      printf("\n");
   }

   printf("\n");
   printf("</pre>\n");
}

include 'ui/footer.php';

?>
<?php

function checkLoot($main, $npcLootTable)
{
   $return = array('meta' => array());

   if (!$npcLootTable || !$npcLootTable['tableEntries']) { return null; }

   $tableEntries = $npcLootTable['tableEntries'];

   $main->debug->trace(9,"Found ".count($tableEntries)." table entries");

   foreach ($tableEntries as $tableId => $lootTableEntry) {
      $lootResults = calculateLootTable($main,$lootTableEntry);
      $totalCount  = 0;

      //print "<pre class=text-white>\n"; print json_encode($lootResults,JSON_PRETTY_PRINT); print "</pre>\n";

      $return['table'][$tableId] = array();

      foreach ($lootResults['meta'] as $metaKey => $metaValue) { 
         if (!isset($return['meta'][$metaKey])) { $return['meta'][$metaKey] = 0; }
         $return['meta'][$metaKey] += is_array($metaValue) ? array_sum($metaValue) : $metaValue;
      }

      foreach ($lootResults['item'] as $itemName => $itemList) { 
         $itemCount = is_array($itemList) ? count($itemList) : $itemList;
         $totalCount += $itemCount;

         if (!isset($return['table'][$tableId]['item'][$itemName])) { $return['table'][$tableId]['item'][$itemName] = 0; }

         $return['table'][$tableId]['item'][$itemName] += $itemCount;
      }
    
      if (!isset($return['table'][$tableId]['total'])) { $return['table'][$tableId]['total'] = 0; }

      $return['table'][$tableId]['total'] += $totalCount;
   }

   return $return;
}

function calculateLootTable($main, $lootTableEntry) {
   $return = array();

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

      $main->debug->trace(9,"*** Checking loot table entry at multiplier($multiplier) with probability($tableChance): dropForced(".json_encode($dropForced).") dropChance($dropChance) doDrop(".json_encode($doDrop).")");

      if ($dropForced) { $main->debug->trace(9,"*** Have not met the minimum multiplier requirements, forcing loot drop"); }

      if ($doDrop) {
         $main->debug->trace(9,"*** Beginning loot drop calculations");
         $lootResults = calculateLootDrop($main,$multiplier,$lootTableEntry);
         if ($lootResults) { $return = array_merge_recursive($return,$lootResults); }
      }

      $multiplierCount++;
   }

   return $return;
}

function calculateLootDrop($main, $counter, $lootTableEntry)
{
   $return           = array('meta' => array('nolimit' => 0, 'mindrop' => 0, 'random1' => 0, 'random2' => 0));
   $currentExpansion = $main->data->currentExpansion();
   $currentExpansion = 0;
   $counter          = sprintf("%2d",$counter);

   if (!$lootTableEntry) { return false; }

   $dropLimit   = $lootTableEntry['droplimit'];
   $minDrop     = $lootTableEntry['mindrop'];
   $lootEntries = $lootTableEntry['dropEntries'];
   $entryCount  = count($lootEntries);

   if (count($lootEntries) == 0) { return false; } 

   if ($dropLimit == 0 && $minDrop == 0) {
      $main->debug->trace(9,"$counter: *** No droplimit or mindrop was specified, process all list items");
      $return['meta']['nolimit']++;
 
      foreach ($lootEntries as $lootEntry) {
        $itemName       = $lootEntry['item_name'];
        $itemChance     = $lootEntry['chance'];
        $itemMultiplier = $lootEntry['multiplier'];

         if (!validExpansion($currentExpansion,$lootEntry['drop_min_expansion'],$lootEntry['drop_max_expansion']) ||
             !validExpansion($currentExpansion,$lootEntry['item_min_expansion'],$lootEntry['item_max_expansion'])) { 
            $main->debug->trace(9,"$counter: !!! Expansion not valid for $itemName");
            continue; 
         }

         checkLootMultiplier($main,$counter,$lootEntry,$return,0,true);
      }

      return $return;
   }

   if ($entryCount > 100 && $dropLimit == 0) { 
      $dropLimit = 10; 
      $main->debug->trace(9,"$counter: *** Loot entries exceeded limit of 100 with no drop limit, force drop limit to $dropLimit");
   }

   if ($dropLimit < $minDrop) { 
      $main->debug->trace(9,"$counter: *** Drop limit ($dropLimit) was less than minimum drops ($minDrop), force drop limit to $minDrop");
      $dropLimit = $minDrop;
   }

   $rollTotalActual = 0;
   $rollTotal       = 0;
   $activeItemList  = false;

   $main->debug->trace(9,"$counter: ### Calculating");

   foreach ($lootEntries as $lootEntry) {
      $itemName       = $lootEntry['item_name'];
      $itemChance     = $lootEntry['chance'];
      $itemMultiplier = $lootEntry['multiplier'];

      if (!validExpansion($currentExpansion,$lootEntry['drop_min_expansion'],$lootEntry['drop_max_expansion']) ||
          !validExpansion($currentExpansion,$lootEntry['item_min_expansion'],$lootEntry['item_max_expansion'])) { 
             $main->debug->trace(9,"$counter: !!! Expansion not valid for $itemName");
             continue; 
      }

      //$main->debug->trace(9,"$counter: *** $itemName adding $itemChance to total");

      $rollTotalActual += $itemChance;
      $activeItemList = true;
   }

   $main->debug->trace(9,"$counter: *** Roll total for table = $rollTotalActual");
   $rollTotal = max($rollTotalActual,100);

   if (!$activeItemList) { return null; }

   $main->debug->trace(9,"$counter: ### Phase 1 - Guaranteed minimum drops");

   if ($minDrop > 0) { $return['meta']['mindrop']++; }

   for ($drop = 0; $drop < $minDrop; $drop++) {
      $roll = randFloat(0,$rollTotalActual);

      $main->debug->trace(9,"$counter: @@@ Rolling for drop(".($drop+1).") minDrop($minDrop): $roll out of $rollTotalActual");

      foreach ($lootEntries as $lootEntry) {
         $itemName       = $lootEntry['item_name'];
         $itemChance     = $lootEntry['chance'];
         $itemMultiplier = $lootEntry['multiplier'];

         if (!validExpansion($currentExpansion,$lootEntry['drop_min_expansion'],$lootEntry['drop_max_expansion']) ||
             !validExpansion($currentExpansion,$lootEntry['item_min_expansion'],$lootEntry['item_max_expansion'])) { 
                $main->debug->trace(9,"$counter: !!! Expansion not valid for $itemName");
                continue; 
         }

         $main->debug->trace(9,"$counter: *** Checking $itemName: $roll < $itemChance");

         if ($roll < $itemChance) {
            addLootDrop($main,$counter,$itemName,$return); 

            $itemMultiplier = max($itemMultiplier,1);

            if ($itemMultiplier > 1) { $main->debug->trace(9,"$counter: *** Checking multiplier on $itemName ($itemMultiplier)"); }

            checkLootMultiplier($main,$counter,$lootEntry,$return);

            break;
         }
         else { $roll -= $itemChance; }
      }
   }

   $main->debug->trace(9,"$counter: ### Phase 2 - Random drops to limit");

   if (($dropLimit - $minDrop) == 1) {
      $return['meta']['random1']++;

      $main->debug->trace(9,"$counter: *** Roll one extra drop, dropLimit($dropLimit) minDrop($minDrop)");

      $roll = randFloat(0,$rollTotal);

      $main->debug->trace(9,"$counter: @@@ Rolling: $roll out of $rollTotal");

      foreach ($lootEntries as $lootEntry) {
         $itemName       = $lootEntry['item_name'];
         $itemChance     = $lootEntry['chance'];
         $itemMultiplier = $lootEntry['multiplier'];

         if (!validExpansion($currentExpansion,$lootEntry['drop_min_expansion'],$lootEntry['drop_max_expansion']) ||
             !validExpansion($currentExpansion,$lootEntry['item_min_expansion'],$lootEntry['item_max_expansion'])) { 
                $main->debug->trace(9,"$counter: !!! Expansion not valid for $itemName");
                continue; 
         }

         $main->debug->trace(9,"$counter: *** Checking $itemName: $roll < $itemChance");

         if ($roll < $itemChance) {
            addLootDrop($main,$counter,$itemName,$return);
            checkLootMultiplier($main,$counter,$lootEntry,$return);
            break;
         }
         else { $roll -= $itemChance; }
      }
   }
   else if ($dropLimit > $minDrop) {
      $return['meta']['random2']++;

      $main->debug->trace(9,"$counter: *** Roll 2 or more extra drops, dropLimit($dropLimit) minDrop($minDrop)");

      $dropCount    = $minDrop;
      $lootList     = array_values($lootEntries);  // provide an integer indexed array
      $itemPosition = array_rand($lootList);

      $main->debug->trace(9,"$counter: *** Starting at random list position($itemPosition)");
      
      for ($loops = 0; $loops < $entryCount; $loops++) {
         if ($dropCount > $dropLimit) { 
            $main->debug->trace(9,"$counter: *** Reached the drop limit, dropCount($dropCount) dropLimit($dropLimit)");
            break; 
         }

         $lootEntry      = $lootList[$itemPosition];
         $itemName       = $lootEntry['item_name'];
         $itemChance     = $lootEntry['chance'];
         $itemMultiplier = $lootEntry['multiplier'];

         if (validExpansion($currentExpansion,$lootEntry['drop_min_expansion'],$lootEntry['drop_max_expansion']) &&
             validExpansion($currentExpansion,$lootEntry['item_min_expansion'],$lootEntry['item_max_expansion'])) { 
            $roll = randFloat(0,100);

            $main->debug->trace(9,"$counter: @@@ Rolling for entry position($itemPosition): $roll out of 100");
            $main->debug->trace(9,"$counter: *** Checking $itemName: $roll < $itemChance");

            if ($roll <= $itemChance) {
               addLootDrop($main,$counter,$itemName,$return); 
               checkLootMultiplier($main,$counter,$lootEntry,$return);
               $dropCount++;
            }
         }

         $itemPosition++;

         if ($itemPosition >= $entryCount) { $itemPosition = 0; }
      }
   }

   $main->debug->trace(9,"$counter: ### Done");

   return $return;
}

function checkLootMultiplier($main, $counter, $lootEntry, &$return, $startAt = 1, $addLoot = false)
{
   $itemName       = $lootEntry['item_name'];
   $itemChance     = $lootEntry['chance'];
   $itemMultiplier = $lootEntry['multiplier'];

   $itemMultiplier = max($itemMultiplier,$startAt);

   if ($itemMultiplier >= $startAt) { $main->debug->trace(9,"$counter: *** Checking multiplier on $itemName ($itemMultiplier)"); }

   $lootAdded = false;

   for ($multiplier = $startAt; $multiplier < $itemMultiplier; ++$multiplier) {
      $roll = randFloat(0,100);

      $main->debug->trace(9,"$counter: @@@ Rolling for multiplier($multiplier) on $itemName: $roll out of 100");
      $main->debug->trace(9,"$counter: *** Checking $itemName: $roll < $itemChance");

      if ($roll <= $itemChance) { 
         if ($addLoot && !$lootAdded) { 
            addLootDrop($main,$counter,$itemName,$return); 
            $lootAdded = true;
         }
      }
   }
}

function addLootDrop($main, $counter, $itemName, &$return)
{
    $main->debug->trace(9,"$counter: $$$ Loot drop assigned: $itemName");

    if (!isset($return['item'][$itemName])) { $return['item'][$itemName] = 0; }

    $return['item'][$itemName]++;
}

function validExpansion($currentExpansion, $minExpansion, $maxExpansion)
{
   if ($currentExpansion == 0) { return true; }

   return (($minExpansion == 0 || ($currentExpansion >= $minExpansion && $currentExpansion < $maxExpansion)) ? true : false);
}

function calculateExpansion($type, $dropExpansion, $itemExpansion)
{
   if ($dropExpansion == 0 && $itemExpansion == 0) { return 0; }

   if ($dropExpansion == 0) { return $itemExpansion; }
   if ($itemExpansion == 0) { return $dropExpansion; }

   if (preg_match('/^min$/i',$type))      { return max($dropExpansion,$itemExpansion); }
   else if (preg_match('/^max$/i',$type)) { return min($dropExpansion,$itemExpansion); }

   return null;
}

function randFloat($min, $max, $precision = 5)
{
    return sprintf("%.".$precision."f",$min + (lcg_value() * ($max - $min)));
}

?>