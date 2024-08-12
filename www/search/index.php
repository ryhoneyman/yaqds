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
$db    = $main->db();

$main->title('Global Search');
$main->pageDescription('Lookup item information globally');

$search      = $input->get('search') ?: null;
$searchValue = trim($input->get('value','all'));
$searchType  = trim($input->get('type','all'));

$searchTypeList = [
   'items.id' => [
      'label' => 'Item ID',
      'scanOrder' => ['items' => 'id','lootdrop_entries' => 'item_id','loottable_entries' => 'lootdrop_id','npc_types' => 'loottable_id','spawnentry' => 'npcID','spawngroup' => 'id','spawn2' => 'spawngroupID','zone' => 'short_name'],
   ],
   'items.Name' => [
      'label' => 'Item Name',
      'scanOrder' => ['items' => 'id','lootdrop_entries' => 'item_id','loottable_entries' => 'lootdrop_id','npc_types' => 'loottable_id','spawnentry' => 'npcID','spawngroup' => 'id','spawn2' => 'spawngroupID','zone' => 'short_name'],
   ],
   'npc_types.id' => [
      'label' => 'NPC ID',
      'scanOrder' => ['npc_types' => 'id','spawnentry' => 'npcID','spawngroup' => 'id','spawn2' => 'spawngroupID','zone' => 'short_name','loottable_entries' => 'loottable_id','lootdrop_entries' => 'lootdrop_id','items' => 'id'],
   ],
   'npc_types.name' => [
      'label' => 'NPC Name',
      'scanOrder' => ['npc_types' => 'name','spawnentry' => 'npcID','spawngroup' => 'id','spawn2' => 'spawngroupID','zone' => 'short_name','loottable_entries' => 'loottable_id','lootdrop_entries' => 'lootdrop_id','items' => 'id'],
   ],
   'spawngroup.id' => [
      'label' => 'Spawngroup ID',
      'scanOrder' => ['spawngroup' => 'id','spawnentry' => 'spawngroupID','npc_types' => 'id','spawn2' => 'spawngroupID','zone' => 'short_name','loottable_entries' => 'loottable_id','lootdrop_entries' => 'lootdrop_id','items' => 'id'],
   ],
   'loottable.id' => [
      'label' => 'Loottable ID',
      'scanOrder' => ['loottable' => 'id', 'loottable_entries' => 'loottable_id','lootdrop_entries' => 'lootdrop_id','items' => 'id','npc_types' => 'loottable_id','spawnentry' => 'npcID','spawngroup' => 'id','spawn2' => 'spawngroupID','zone' => 'short_name'],
   ], 
];

$searchSelect = array_combine(array_keys($searchTypeList),array_column($searchTypeList,'label'));

$searchTables = [
   'items' => [
      'select' => '*',
      'search' => [
         'id' => [
            'quote' => false,
         ],
         'Name' => [
            'quote' => true,
         ],
      ],
      'link' => [
         'lootdrop_entries.item_id' => 'id',
      ],
   ],
   'zone' => [
      'select' => '*',
      'search' => [
         'short_name' => [
            'quote' => true,
         ],
      ],
      'link' => [],
   ],
   'spawn2' => [
      'select' => '*',
      'search' => [
         'id' => [
            'quote' => false,
         ],
         'spawngroupID' => [
            'quote' => false,
         ],
      ],
      'link' => [
         'zone.short_name' => 'zone',
      ],
   ],
   'spawngroup' => [
      'select' => '*',
      'search' => [
         'id' => [
            'quote' => false,
         ],
      ],
      'link' => [
         'spawn2.spawngroupID' => 'id',
         'spawnentry.spawngroupID' => 'id',
      ],
   ],
   'spawnentry' => [
      'select' => '*',
      'search' => [
         'spawngroupID' => [
            'quote' => false,
         ],
         'npcID' => [
            'quote' => false,
         ],
      ],
      'link' => [
         'spawngroup.id' => 'spawngroupID',
         'spawn2.spawngroupID' => 'spawngroupID',
         'npc_types.id' => 'npcID',
      ],
   ],
   'npc_types' => [
      'select' => '*',
      'search' => [
         'id' => [
            'quote' => false,
         ],
         'name' => [
            'quote' => true,
         ],
         'loottable_id' => [
            'quote' => false,
         ]
      ],
      'link' => [
         'loottable.id' => 'loottable_id',
         'loottable_entries.loottable_id' => 'loottable_id',
         'spawnentry.npcID' => 'id',
      ],
   ],
   'loottable' => [
      'select' => '*',
      'search' => [
         'id' => [
            'quote' => false,
         ],
      ],
      'link' => [
         'loottable_entries.loottable_id' => 'id',
         'lootdrop_entries.lootdrop_id' => 'lootdrop_id',
      ],
   ],
   'loottable_entries' => [
      'select' => '*',
      'search' => [
         'loottable_id' => [
            'quote' => false,
         ],
      ],
      'link' => [
         'lootdrop.id' => 'lootdrop_id',
         'lootdrop_entries.lootdrop_id' => 'lootdrop_id',
         'npc_types.loottable_id' => 'loottable_id',
      ],
   ],
   'lootdrop_entries' => [
      'select' => '*',
      'search' => [
         'lootdrop_id' => [
            'quote' => false,
         ],
         'item_id' => [
            'quote' => false,
         ],
      ],
      'link' => [
         'lootdrop.id' => 'lootdrop_id',
         'items.id' => 'item_id',
         'loottable_entries.lootdrop_id' => 'lootdrop_id',
      ],
   ],
];

include 'ui/header.php';

print "<style>\n".
      ".select2-results__option { line-height:1.0; }\n".
      ".select2-container--default .select2-results>.select2-results__options { max-height: 350px; }\n".
      "</style>\n";


print $alte->displayCard($alte->displayRow(
         $html->startForm().
         "<div class='input-group' style='width:fit-content;'>".   
         $html->select('type',$searchSelect,$searchType). 
         $html->inputText('value',$searchValue).
         "<div class='ml-2'>".$html->submit('search','Exact')."</div>".
         "<div class='ml-2'>".$html->submit('search','Like',array('class' => 'btn-wide btn btn-success'))."</div>".
         "</div>".
         $html->endForm(),
         array('container' => 'col-xl-6 col-12')
      ),array('container' => 'col-xl-6 col-12'));


$displayResult = [];

if ($search) {
   $searchResults = performSearch($db,$searchTables,$searchTypeList,$searchType,$searchValue,$search);
   
   foreach ($searchResults as $tableName => $tableData) {
      $displayResult[$tableName] = $alte->displayCard($html->table($tableData,null,['table.id' => $tableName, 'datatable' => true]),array('title' => $tableName, 'container' => 'col-12'));
   }

   ksort($displayResult);

   print implode('',$displayResult);
}


//print "<script type='text/javascript'>\n".
//      "   $('#npc').select2();\n".
//      "</script>\n";

include 'ui/footer.php';

?>
<?php

function performSearch($db, $searchTables, $searchTypeList, $searchType, $searchValue, $searchMatch)
{
   $return     = [];
   $known      = [$searchType => [$searchValue]];
   $exact      = (preg_match('/^exact$/i',$searchMatch)) ? true : false;
   $scanTables = $searchTypeList[$searchType]['scanOrder'];

   foreach ($scanTables as $tableName => $tableSearchCol) {
      $tableSearchType = sprintf("%s.%s",$tableName,$tableSearchCol);
      $tableParams     = $searchTables[$tableName];
      $tableSearchInfo = $tableParams['search'][$tableSearchCol];

      //print "<p>\n"; var_dump($known); print "<br>\n"; print "Trying search $tableName ($tableSearchType)<br>\n";
      
      if (!$known[$tableSearchType]) { 
         print "No known values to search $tableName<br>\n"; 
         continue; 
      }

      $quote  = $tableSearchInfo['quote'];
      $select = $tableParams['select'] ?: '*';
      $where  = $tableSearchCol;
      $values = $known[$tableSearchType];

      $results = searchTable($db,$tableName,$quote,$exact,$select,$where,$values);

      if ($results) {
         $return[$tableName] = $results;

         //print count($results)." results for $tableName<br>\n";
       
         foreach ($searchTables[$tableName]['link'] as $knownId => $returnId) {
            $linkValues = [];
            foreach ($results as $result) { 
               //printf("Adding %s (%s) for %s<br>\n",$result[$returnId],$returnId,$knownId);
               $linkValues[] = $result[$returnId];
      
            }
            $known[$knownId] = array_filter(array_unique($linkValues));
         }
      }
      else { print "No results for $tableName<br>\n"; }

      $exact = true;
   }

   return $return;
}

function searchTable($db, $tableName, $quote, $exact, $select, $where, $values)
{
   if (!is_array($values)) { $values = [$values]; }

   $whereClause = [];
   
   foreach ($values as $value) {
      $whereClause[] = ($quote) ? (($exact) ? sprintf("`%s` = \"%s\"",$where,$value) : sprintf("`%s` LIKE \"%%%s%%\"",$where,preg_replace('/\s+/','%%',$value))) : sprintf("`%s` = %d",$where,$value);
   }

   $query = sprintf("SELECT %s FROM %s WHERE %s",$select,$tableName,implode(' OR ',$whereClause));

   //print "$query<br>\n";

   $result = $db->query($query,['autoindex' => true]);

   return $result;
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