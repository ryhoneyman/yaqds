<?php

//    Copyright 2023 - Ryan Honeyman

include_once 'common/base.class.php';

class Data extends Base
{
   protected $version  = 1.0;

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class.php
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);

      if ($options['db']) { $this->db = $options['db']; }
   }

   public function getZoneInfoByName($zoneName)
   {
      $this->debug(8,"called");

      if (!$this->databaseAvail()) { $this->error('database not available'); return false; }

      if (!preg_match('/^\w+$/',$zoneName)) { $this->error('invalid zoneName provided'); return false; }

      return $this->db->query("SELECT * FROM zone WHERE short_name = '$zoneName'",array('multi' => false));
   }

   public function getZones($keyId = null, $columns = null, $expansion = null)
   {
      $this->debug(8,"called");

      if (!$this->databaseAvail()) { $this->error('database not available'); return false; }

      if (!is_null($expansion) && !preg_match('/^[\d\.]+$/',$expansion)) { $this->error('invalid expansion provided'); return false; }

      if (is_null($keyId)) { $keyId = 'zoneidnumber'; }
      if (is_null($columns)) { $columns = '*'; }

      if (!is_array($columns)) { $columns = array($columns); }
 
      $columnList = implode(', ',$columns);

      $query = "SELECT $columnList \n".
               "FROM zone \n".
               ((is_null($expansion)) ? '' : "WHERE expansion <= $expansion").
               '';

      return $this->db->query($query,array('keyid' => $keyId));
   }

   public function getSpawnGridsByZoneName($zoneName)
   {
      $this->debug(8,"called");

      $return = array();

      if (!$this->databaseAvail()) { $this->error('database not available'); return false; }

      if (!preg_match('/^\w+$/',$zoneName)) { $this->error('invalid zoneId provided'); return false; }
   
      $query = "SELECT ge.*, concat(ge.gridid,'.',ge.zoneid,'.',ge.number) as keyid \n".
               "FROM grid_entries ge \n".
               "LEFT JOIN zone z ON z.zoneidnumber = ge.zoneid \n".
               "WHERE z.short_name = '$zoneName'";

      $gridList = $this->db->query($query,array('keyid' => 'keyid'));
   
      if (!$gridList) { return $return; }
   
      foreach ($gridList as $keyId => $gridInfo) {
         $return[$gridInfo['gridid']][$gridInfo['number']] = $gridInfo;
      }
   
      return $return;
   }

   public function getMapSpawnInfoByZoneName($zoneName, $zoneFloor = null, $zoneCeil = null, $expansion = null)
   {
      $this->debug(8,"called");

      if (!$this->databaseAvail()) { $this->error('database not available'); return false; }

      // Make sure these values are sanitized
      if (!preg_match('/^\w+$/',$zoneName)) { $this->error('invalid zoneName provided'); return false; }
   
      if (!is_null($zoneFloor) && !preg_match('/^[\d\-]+$/',$zoneFloor)) { $this->error('invalid zoneFloor provided'); return false; }
      if (!is_null($zoneCeil) && !preg_match('/^[\d\-]+$/',$zoneCeil)) { $this->error('invalid zoneCeil provided'); return false; }
      if (!is_null($expansion) && !preg_match('/^[\d\.]+$/',$expansion)) { $this->error('invalid expansion provided'); return false; }

      $query = "SELECT concat(se.spawngroupID,'.',se.npcID,'.',s2.x,'.',s2.y) as keyid, z.short_name, z.zoneidnumber as zoneID, nt.name, nt.level, sg.id as sgID, \n".
               "       nt.id as npcID, s2.min_expansion as spawnMinEx, s2.max_expansion as spawnMaxEx, se.min_expansion as entryMinEx, se.max_expansion as entryMaxEx, \n".
               "       se.chance, s2.x, s2.y, s2.z, s2.heading, s2.pathgrid as gridID \n".
               "FROM spawn2 s2 \n".
               "LEFT JOIN spawngroup sg ON s2.spawngroupID = sg.id \n".
               "LEFT JOIN spawnentry se ON se.spawngroupID = sg.id \n".
               "LEFT JOIN npc_types nt  ON nt.id = se.npcID \n".
               "LEFT JOIN zone z        ON z.short_name = s2.zone \n".
               "WHERE z.short_name = '$zoneName' \n".
               ((is_null($expansion)) ? '' :
               "AND   (((se.min_expansion <= $expansion or se.min_expansion = 0) and (se.max_expansion >= $expansion or se.max_expansion = 0)) \n".
               "       AND ((s2.min_expansion <= $expansion or s2.min_expansion = 0) and (s2.max_expansion >= $expansion or s2.max_expansion = 0))) \n".
               "AND z.expansion <= $expansion \n").
               "AND nt.bodytype < 64 \n".
               ((is_null($zoneFloor)) ? '' : "AND s2.z >= $zoneFloor \n").
               ((is_null($zoneCeil)) ? '' : "AND s2.z <= $zoneCeil \n").
               "ORDER BY sg.id, s2.x, s2.y, s2.z";
      
      return $this->db->query($query);
   }

   public function databaseAvail()
   {
      return ((is_a($this->db,'MySQL') && $this->db->isConnected()) ? true : false);
   }
}
?>
