<?php

//    Copyright 2023 - Ryan Honeyman

include_once 'common/constantsbase.class.php';

class Constants extends ConstantsBase
{
   public function currentExpansion() { return $this->fetch('currentExpansion'); }

   public function expansionList() 
   { 
      $expansions    = $this->fetch('expansions'); 
      $expansionInfo = $this->fetch('expansionInfo');

      $return = array();

      foreach ($expansions as $expansionId => $expansionData) {
         $return[$expansionId] = array('data' => $expansionData, 'info' => $expansionInfo[$expansionData['id']]);
      }

      return $return;
   }

   public function getZoneMapData($zoneName = null)
   {
      $mapData = $this->fetch('zone.map');

      return ((is_null($zoneName)) ? $mapData : $mapData[$zoneName]);
   }
}
?>
