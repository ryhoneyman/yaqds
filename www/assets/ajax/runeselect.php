<?php
include_once 'miners-settlement-init.php';
include_once 'local/minersmain.class.php';

$main = new MinersMain(array(
   'debugLevel'     => 0,
   'errorReporting' => false,
   'sessionStart'   => array('read_and_close' => true),
   'memoryLimit'    => null,
   'sendHeaders'    => true,
   'database'       => true,
   'input'          => true,
   'html'           => true,
   'adminlte'       => true,
));

$main->buildClass('constants','Constants',null,'local/constants.class.php');

$input = $main->obj('input');
$html  = $main->obj('html');

print pageDisplay($main);

?>
<?php

function pageDisplay($main)
{
   $html      = $main->obj('html');
   $alte      = $main->obj('adminlte');
   $attribs   = $main->obj('constants')->attribs();
   $caller    = strtolower($main->obj('input')->get('caller','alphanumeric,underscore,dash,dot,forwardslash'));
   $gearState = json_decode($main->obj('input')->get('state','all'),true);

   if (!$gearState || !$caller) { return ''; }
 
   $runeInput  = array();
   $hashLookup = array();

   if ($caller == 'scalablesim') {
      $runeInput = ($main->sessionValue('scalablesim/pageInput'))['runes'] ?: array();
      $hashLookup = $main->getItemGearHashList();
      $gearLookup = $main->getItemGearList(); 
   }
   else if ($caller == 'buildsim') {
      $runeInput = ($main->sessionValue('buildsim/pageInput'))['runes'] ?: array();
      $hashLookup = $main->getPlayerGearHashList();
      $gearLookup = $main->getPlayerGearList();
   }
   else if ($caller == 'playerbuild') {
      $runeInput = ($main->sessionValue('playerbuild/pageInput'))['runes'] ?: array();
      $hashLookup = $main->getPlayerGearHashList();
      $gearLookup = $main->getPlayerGearList();
   }

   $runeSelectList = array();
   $runewordList   = $main->getRunewordList();

   $gearNames = array();
   foreach ($main->obj('constants')->gearTypes() as $gearType => $gearTypeLabel) {
      if (!preg_match('/^(none|)$/i',$gearState[$gearType])) { 
         $gearHash = $gearState[$gearType]; 
         $gearId   = $hashLookup[$gearHash];
         $gearName = $gearLookup[$gearId]['name'];
 
         if ($gearName) { $gearNames[$gearName] = $gearTypeLabel; }
      }
   }

   foreach ($runewordList as $runeId => $runeInfo) {
      $runeRequires = json_decode($runeInfo['requires'],true);

      if ($runeRequires) {
         $runeCheck = array_values(array_intersect_key($gearNames,array_flip($runeRequires)));
         if (count($runeRequires) > count($runeCheck)) { continue; }
      }

      $runeSection = (!$runeRequires) ? 'General' : implode(' and ',$runeCheck);

      $runeList[$runeSection][$runeInfo['name']] = $runeInfo['label'];
   }

   $selectOpts = array('id' => 'runes', 'class' => 'form-control runes', 'multi' => true);

   return $html->select('runes',$runeList,$runeInput,$selectOpts);
}

?>
