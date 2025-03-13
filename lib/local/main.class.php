<?php

//    Copyright 2023 - Ryan Honeyman

include_once 'common/mainbase.class.php';

class Main extends LWPLib\MainBase
{
   public $userId       = null;
   public $hashTypes    = null;
   public $yaqdsVersion = null;
   public $quarmDb      = null;
   public $data         = null;
   public $map          = null;

   public function __construct($options = null)
   {
      parent::__construct($options);

      $versionFile = sprintf("%s/version.json",APP_CONFIGDIR);
      $versionInfo = [];

      if (file_exists($versionFile)) { $versionInfo = json_decode(file_get_contents($versionFile),true); }

      $this->yaqdsVersion = $versionInfo['yaqds'] ?: 'Unknown';
      $this->quarmDb      = $versionInfo['quarmDb'] ?: 'Unknown';
   }

   public function title($name = null)
   {
      if (is_null($name)) { return $this->var('title'); }

      $this->var('title',$name);
   }

   public function pageDescription($description = null)
   {
      if (is_null($description)) { return $this->var('pageDescription'); }

      $this->var('pageDescription',$description);
   }

   public function initialize($options)
   {
      parent::initialize($options);

      if (isset($options['data']) && $options['data']) {
         if (!$this->buildClass('data','Data',array('db' => $this->db(), 'file' => $options['data']),'local/data.class.php')) { exit; }
         $this->data = $this->obj('data');
      }
   
      if (isset($options['map']) && $options['map']) {
         if (!$this->buildClass('map','Map',null,'local/map.class.php')) { exit; }
         $this->map = $this->obj('map');
      }

      return true;
   }
}
