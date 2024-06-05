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
$expansion  = $input->get('expansion','alphanumeric');

$useCurrentExpansion = (preg_match('/^current$/i',$expansion)) ? true : false;

$main->var('useExpansion',($useCurrentExpansion) ? $main->data->currentExpansion() : ((preg_match('/^[\d\.]+$/',$expansion)) ? $expansion : 0));

if ($iterations <= 0 || $iterations > 10000) { $iterations = 10000; }

if ($sample) { $iterations = 1; }

$npcLootTableList = $main->data->getNpcLootTableList(array('sort' => true));

include 'ui/header.php';

print "<style>\n".
      ".select2-results__option { line-height:1.0; }\n".
      ".select2-container--default .select2-results>.select2-results__options { max-height: 350px; }\n".
      "</style>\n";

$npcList = array();
foreach ($npcLootTableList['data'] as $index => $data) { 
   ksort($data['zone']);

   $zoneList = implode('/',array_keys($data['zone']));
   $lootExp  = ($data['min_expansion'] != 0 || $data['max_expansion'] != 0) ? sprintf("%1.1f-%1.1f; ",$data['min_expansion'],$data['max_expansion']) : '';
   $npcList[$data['hash']] = sprintf("%s (%s%s)",$data['name'],$lootExp,$zoneList); 
}

print $alte->displayCard($alte->displayRow(
         $html->startForm().
         "<div class='input-group' style='width:fit-content;'>".    
         $html->select('npc',$npcList,$npcHash).
         "<div class='ml-2'>".$html->submit('analyze','Analyze Chances')."</div>".
         "<div class='ml-2'>".$html->submit('sample','Simulate Drops',array('class' => 'btn-wide btn btn-success'))."</div>".
         "<div class='ml-2 mt-2 align-items-center'>".$html->checkbox('expansion','current',$expansion)."use current expansion</div>".
         "</div>".
         $html->endForm(),
         array('container' => 'col-xl-9 col-12')
      ),array('container' => 'col-xl-9 col-12'));

$lootTables = array();
$itemLookup = array();

if ($npcHash && $npcLootTableList['lookup'][$npcHash]) {
   $npcIndex         = $npcLootTableList['lookup'][$npcHash];
   $lootTableId      = $npcLootTableList['data'][$npcIndex]['loottable_id'];
   $lootTableEntries = $main->data->getLootTableEntriesById($lootTableId);

   $lootTables['npc_name']     = $npcLootTableList['data'][$npcIndex]['name'];
   $lootTables['tableEntries'] = $lootTableEntries;

   //print "<pre class='text-white'>\n";

   foreach ($lootTableEntries as $tableId => $entryData) {
      $dropEntries = $main->data->getLootDropEntriesById($entryData['lootdrop_id']);
      $lootTables['tableEntries'][$tableId]['dropEntries'] = $dropEntries;

      foreach ($dropEntries as $id => $lootEntry) {
         $itemName         = $lootEntry['item_name'];
         $dropMinExpansion = $lootEntry['drop_min_expansion'];
         $dropMaxExpansion = $lootEntry['drop_max_expansion'];
         $itemMinExpansion = $lootEntry['item_min_expansion'];
         $itemMaxExpansion = $lootEntry['item_max_expansion'];  
         $minExpansion     = calculateExpansion('min',$dropMinExpansion,$itemMinExpansion);
         $maxExpansion     = calculateExpansion('max',$dropMaxExpansion,$itemMaxExpansion);

         //printf("%s drops(%s-%s) allowed(%s-%s) using(%s-%s)\n",
         //       $itemName,$dropMinExpansion,$dropMaxExpansion,$itemMinExpansion,$itemMaxExpansion,$minExpansion,$maxExpansion);

         $lootTables['tableEntries'][$tableId]['dropEntries'][$id]['min_expansion'] = $minExpansion;
         $lootTables['tableEntries'][$tableId]['dropEntries'][$id]['max_expansion'] = $maxExpansion;

         $itemLookup[$tableId][$itemName] = [
            'min_expansion' => $minExpansion,
            'max_expansion' => $maxExpansion,
         ]; 
      }
   }

   //print "</pre>\n"; 
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

if ($main->debug->level() >= 9) {
   print "<pre class='text-white'>\n"; print json_encode($stats,JSON_PRETTY_PRINT); print "</pre>\n";
}

if ($sample) {
   $statTables = $stats['table'];

   $lootDisplay = "<pre class='text-white'>\n";

   $dropCount = 0;

   foreach ($statTables as $tableId => $tableLootEntry) {
      $lootTableItems = $tableLootEntry['item'] ?: array();

      foreach ($lootTableItems as $itemName => $itemCount) { 
         $itemInfo       = $itemLookup[$tableId][$itemName] ?: array();
         $minExpansion   = $itemInfo['min_expansion'];
         $maxExpansion   = $itemInfo['max_expansion'];
         $itemExpansion  = (!$minExpansion && !$maxExpansion) ? '' : sprintf("<b class='text-primary'>expansion(%s-%s)</b>",$minExpansion,$maxExpansion);

         $lootDisplay .= sprintf("%dx %s %s\n",$itemCount,$itemName,$itemExpansion);

         $dropCount++;
      }
   }

   if ($dropCount == 0) { $lootDisplay .= "None\n"; }

   $lootDisplay .= "</pre>\n";

   print $alte->displayCard($alte->displayRow(
            $lootDisplay,
            array('container' => 'col-xl-9 col-12')
         ),array('container' => 'col-xl-9 col-12', 'title' => sprintf("Simulated loot results for %s",$npc), 'card' => 'card-warning'));

   $lootTableEntries = $lootTables['tableEntries'] ?: array();
   $lootTableCount   = count($lootTableEntries);

   $explanation = "<div class='mb-4'>This mob has ".$lootTableCount." loot table".(($lootTableCount != 1) ? 's' : '').":</div>";

   $lootTableColumns = array('loottable_id','lootdrop_id','id','dropEntries');
   $lootDropColumns  = array('item_id','item_name','chance','multiplier','min_expansion','max_expansion');

   foreach ($lootTableEntries as $tableId => $tableData) {
      $lootOutcome   = describeLootOutcome($tableData);
      $lootDropRange = ($lootOutcome['mindrops'] == $lootOutcome['maxdrops']) ? sprintf("%d drop%s",$lootOutcome['maxdrops'],($lootOutcome['maxdrops'] != 1) ? 's' :'') :
                                                                                sprintf("%d - %d drops",$lootOutcome['mindrops'],$lootOutcome['maxdrops']);

      $title = sprintf("<span class='text-warning'>TableID %s, DropID %s</span> <span class='text-orange ml-3'>(%s)</span>",
                       $tableData['loottable_id'],$tableData['lootdrop_id'],$lootDropRange);                                                                         

      $explanation .= $alte->displayCard($alte->displayRow(
                        $html->table(array(array_diff_key($tableData,array_flip($lootTableColumns))),null,array('table.class' => 'table text-green text-bold')).
                        $html->table($tableData['dropEntries'],$lootDropColumns,array('table.class' => 'table table-striped small')).
                        "<div class='mt-2 text-lightblue'>".$lootOutcome['description']."</div>",
                        array('container' => 'col-12')
                      ),array('container' => 'col-12', 'title' => $title, 'card' => 'card-secondary'));              
   }

   print $alte->displayCard($alte->displayRow(
      $explanation,
      array('container' => 'col-xl-9 col-12')
   ),array('container' => 'col-xl-9 col-12', 'title' => 'Explanation', 'card' => 'card-purple'));
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
         $itemExpansion  = (!$minExpansion && !$maxExpansion) ? '' : sprintf("<b class='text-primary'>expansion(%s-%s)</b>",$minExpansion,$maxExpansion);

         printf("  %5.1f%% %s dropped(%d) relativeTableChance(%1.1f%%) %s\n",$globalPercent,$itemName,$itemCount,$perKillPercent,$itemExpansion);
      }

      printf("\n");
   }

   printf("\n");
   printf("</pre>\n");
}

print "<script type='text/javascript'>\n".
      "   $('#npc').select2();\n".
      "</script>\n";

include 'ui/footer.php';

?>
<?php

function describeLootOutcome($tableData)
{
   $multiplier       = $tableData['multiplier'];
   $multiplierMin    = $tableData['multiplier_min'];
   $probability      = $tableData['probability'];
   $dropEntries      = $tableData['dropEntries'] ?: array();
   $dropCount        = count($dropEntries);
   $dropChances      = array_count_values(array_map('intval',array_column($dropEntries,'chance')));
   $dropNoChance     = $dropChances["0"];
   $dropAlwaysChance = $dropChances["100"];
   $dropLimit        = $tableData['droplimit'];
   $minDrop          = $tableData['mindrop'];

   $description = '';

   if ($dropLimit < $minDrop)        { $dropLimit = $minDrop; }
   if ($multiplierMin > $multiplier) { $multiplierMin = $multiplier; }

   if ($dropLimit == 0 && $minDrop == 0) { 
      $maxDrops = $multiplier * $dropCount;
      $minDrops = ($probability == 100 && $dropAlwaysChance > 0) ? $dropAlwaysChance : 0;

      $description = "When no drop limit or minimum drop is set in the loot table, there's no guarantee that something will drop. ".
                     (($probability == 100 && $dropAlwaysChance > 0) ? "However in this case, the probability is 100% and one or more item chances are also 100%. ".
                     "This means the minimum drop is the count of list items at 100% chance (which is $minDrops). " : '').
                     "The maximum amount of drops will be multiplier ($multiplier) times number of drop items ($dropCount).";
   }
   else if ($dropCount == 0 || ($dropNoChance == $dropCount)) {
      $maxDrops = 0;
      $minDrops = 0;

      $description = ($dropCount == 0) ? "This loot table references a drop list that is blank or has item that do not exist in the selected expansion.  Therefore, no drops will occur." :
                                         "This loot table contains a drop list with items that do not have any chance to drop, so no drops can occur.";
   }
   else if ($dropLimit > 0 && $minDrop == 0) {
      $maxDrops = $multiplier * min($dropLimit,$dropCount);
      $minDrops = 0;

      $description = "When drop limit is greater than 0 and the minimum drop is 0, there's no guarantee that something will drop, and the maximum amount of drops will be ".
                     " multiplier ($multiplier) times the lesser between drop limit ($dropLimit) and the number of drop items ($dropCount).";
   }
   else {
      $maxDrops = $multiplier * $dropLimit;
      $minDrops = ($probability == 100) ? ($multiplier * $minDrop) : (($multiplierMin > 0) ? $multiplierMin : 0);

      $description = ($probability == 100) ? "This table will set minimum drops to multiplier ($multiplier) times minimum drop ($minDrop) due to probability at 100%." :
                     ((($multiplierMin > 0)) ? "This table will have minimum drops equal to multiplier_min ($multiplierMin)." : "This table has no guaranteed drops.");

      $description .= " The maximum amount of drops is multiplier ($multiplier) times drop limit ($dropLimit).";

      if ($minDrops > 0 && $minDrops < $maxDrops) { 
         $description .= " There is a $probability% chance of additional drops after the guaranteed $minDrops drop".(($minDrops != 1) ? 's' :'')."."; 
      }
   }

   $description .= " The result of this table produces ".(($minDrops == $maxDrops) ? "$minDrops drop".(($minDrops != 1) ? 's' : '') : "$minDrops to $maxDrops drops").".";

   $return = array(
      'mindrops'    => $minDrops,
      'maxdrops'    => $maxDrops,
      'description' => $description,
   );

   return $return;
}

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
   $currentExpansion = $main->var('useExpansion');
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
         if ($dropCount >= $dropLimit) { 
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

    $return['meta']['itemCount']++;

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