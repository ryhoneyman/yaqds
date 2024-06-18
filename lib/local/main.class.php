<?php

//    Copyright 2023 - Ryan Honeyman

include_once 'common/mainbase.class.php';

class Main extends LWPLib\MainBase
{
   public $userId         = null;
   public $hashTypes      = null;
   public $currentVersion = '1.3.1';
   public $quarmDb        = '20240618-1130';
   public $data           = null;
   public $map            = null;

   public function __construct($options = null)
   {
      parent::__construct($options);
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

      if ($options['data']) {
         if (!$this->buildClass('data','Data',array('db' => $this->db(), 'file' => $options['data']),'local/data.class.php')) { exit; }
         $this->data = $this->obj('data');
      }
   
      if ($options['map']) {
         if (!$this->buildClass('map','Map',null,'local/map.class.php')) { exit; }
         $this->map = $this->obj('map');
      }

      return true;
   }
}
