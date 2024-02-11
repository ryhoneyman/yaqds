<?php

include_once 'base.class.php';
include_once 'debug.class.php';

class MainBase extends Base
{
   protected $version     = 1.0;
   public    $debug       = null;
   public    $settings    = array();
   public    $objects     = array();
   public    $classList   = array();
   public    $now         = null;
   public    $pid         = null;
   public    $hostname    = null;
   public    $userName    = null;
   public    $userInfo    = array();
   public    $pageUri     = null;
   public    $autoLoad    = false;
   public    $cliApp      = null;
   public    $webApp      = null;

   public function __construct($options = null)
   {
      $this->cliApp = (php_sapi_name() == "cli") ? true : false;
      $this->webApp = !$this->cliApp;

      if ($options['database']) { $this->settings['need.database'] = true; }

      // Pre-web startup hooks
      if ($this->webApp) { $this->webhookStartup(); }

      // session control must be the very first thing we address, due to header interactions
      if (isset($options['sessionStart'])) { $this->sessionStart($options['sessionStart']); }

      // Logout hook
      if ($this->webApp) { if ($this->webhookLogout()) { exit; } }

      // must turn on error reporting, if requested, immediately to catch errors
      if (isset($options['errorReporting'])) { $this->enableErrorReporting($options['errorReporting']); }

      $this->debug = new Debug();

      $this->objects['debug'] = $this->debug;

      $this->pid      = getmypid();
      $this->hostname = php_uname('n');
      $this->now      = time();

      if ($this->cliApp) { $this->debugType(DEBUG_CLI); }

      $this->settings['defaults'] = array(
         'db.name' => 'default',
      );

      if ($options['memoryLimit']) { $this->setMemoryLimit($options['memoryLimit']); }

      // Class initialization
      $this->initialize($options);

      // Pre-initialize startup hooks
      if ($this->webApp) { 
         $this->setDefaultTimezone($options['timezone']);
         $this->webhookInit();
      }

      if ($options['sendCookies']) { $this->sendCookies($options['sendCookies']); }
      if ($options['sendHeaders']) { $this->sendHeaders($options['sendHeaders']); }

      if ($this->webApp) {
         $this->pageUri            = $_SERVER['SCRIPT_NAME'];
         $this->userInfo['ipAddr'] = ($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
      }
      else {
         $this->userName = get_current_user().'@'.$this->hostname;
         $this->userInfo['ipAddr'] = gethostbyname($this->hostname);
      }

      // Final web hooks
      if ($this->webApp) { $this->webhookFinal(); }
   }

   public function webhookFinal()   { return true; }
   public function webhookInit()    { return true; }
   public function webhookLogout()  { return false; }
   public function webhookStartup() { return true; }

   public function getInputVariables()
   {
      $return = array();

      foreach ($_GET as $key => $value)  { $return[$key] = $value; }
      foreach ($_POST as $key => $value) { $return[$key] = $value; }

      return $return;
   }

   public function sendCookies($cookies = null)
   {
      if (!is_array($cookies)) { return true; }

      foreach ($cookies as $cookieName => $cookieInfo) {
         setcookie($cookieName,$cookieInfo['value'],$cookieInfo['expires'],$cookieInfo['path'],$cookieInfo['domain'],$cookieInfo['secure'],$cookieInfo['httponly']);
      }

      return true;
   }

   public function sendHeaders($headers = null)
   {
      header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
      header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
      header("Cache-Control: no-store, no-cache, must-revalidate");
      header("Cache-Control: post-check=0, pre-check=0", false);
      header("Pragma: no-cache");

      if (!is_array($headers)) { return true; }

      foreach ($headers as $headerName => $headerValue) { header("$headerName: $headerValue"); }

      return true;
   }

   public function redirect($url, $options = null)
   {
      header("Location: $url"); 
      exit;
   }

   public function setDefaultTimezone($tz = null)
   {
      if (is_null($tz)) { $tz = 'Etc/UTC'; }

      date_default_timezone_set($tz);
   }

   public function setMemoryLimit($limit = null)
   {
      $this->debug(8,"called");

      if (!is_null($limit)) { ini_set('memory_limit',$limit); return true; }

      return null;
   }

   public function enableErrorReporting($errorLevel = null)
   {
      $this->debug(8,"called");

      if ($errorLevel === false) { return null; }

      if (is_null($errorLevel) || $errorLevel === true) { $errorLevel = E_ALL & ~E_NOTICE; }

      ini_set('display_errors',1);
      ini_set('display_startup_errors',1);
      error_reporting($errorLevel);

      return true;
   }

   public function sessionValue($name, $value = null, $clear = false)
   {
      if ($clear) { unset($_SESSION[$name]); return null; }

      if (!is_null($value)) { $_SESSION[$name] = $value; }

      return $_SESSION[$name]; 
   }

   public function sessionStart($options = null)
   {
      $this->debug(8,"called");

      if (is_null($options) || $options === false) { return null; }

      $sessionOptions = ($options === true) ? array() : $options;

      return session_start($sessionOptions);
   }

   public function initialize($options)
   {
      if ($options['debugLevel']) { $this->debugLevel($options['debugLevel']); }

      $this->debug(8,"called");

      // if define is not in array format, convert it to array
      if (!is_array($options['define'])) { $options['define'] = ($options['define']) ? array($options['define']) : array(); }

      if ($options['database']) {
         if (!$this->connectDatabase()) { $this->debug(0,"Could not establish connection to database"); exit; }
      }

      // load defines.  we have to do this directly because no data providers are loaded yet.
      if ($options['define']) {
         $this->loadDefinesFromDB($options['define']);
      }

      if ($options['request']) {
         if (!$this->buildClass('request','Request',null,'request.class.php')) { exit; }
      }

      if ($options['input']) {
         if (!$this->buildClass('input','Input',null,'input.class.php')) { exit; }
      }

      if ($options['html']) {
         if (!$this->buildClass('html','HTML',null,'html.class.php')) { exit; }
      }

      if ($options['adminlte']) {
         if (!$this->buildClass('adminlte','AdminLTE',null,'adminlte.class.php')) { exit; }
      }

      if ($options['toastr']) {
         if (!$this->buildClass('toastr','Toastr',null,'toastr.class.php')) { exit; }
      }

      if ($options['require']) {
         foreach ($options['require'] as $buildParams) {
            if (count($buildParams) < 4) { $this->debug(0,"Invalid paramters to buildClass: ".json_encode($buildParams)); continue; }

            if (!call_user_func_array(array($this,'buildClass'),$buildParams)) { exit; }
         }
      }
   }

   public function loadDefinesFromDB($list = null)
   {
      if ($this->connectDatabase() === false) { return false; }

      // load defines.  we have to do this directly because no data providers are loaded yet.
      $defineList = array_map(function($value) { return "name like '".preg_replace('/[^\w\_\%]/','',$value)."'"; },array_unique($list));

      $query   = "SELECT name,value FROM define WHERE (".implode(' OR ',$defineList).")";
      $defines = $this->db()->query($query);

      if (!$defines) { return false; }

      foreach ($defines as $id => $info) {
         if (!defined($info['name'])) { define($info['name'],$info['value']); }
      }

      if (!$this->settings['need.database']) { $this->disconnectDatabase(); }

      return true;
   }

   public function disconnectDatabase($name = null)
   {
      $this->debug(8,"called");

      if (is_a($this->db($name),$className) && !$this->db($name)->isConnected()) { return true; }

      if (is_null($name)) { $name = $this->settings['defaults']['db.name']; }

      if ($this->db($name)) { return $this->db($name)->disconnect(); }

      return false;
   }

   public function connectDatabase($dbConfigFile = null, $name = null, $className = 'MySQL', $fileName = 'mysql.class.php')
   {
      $this->debug(8,"called");

      if (is_a($this->db($name),$className) && $this->db($name)->isConnected()) { return true; }

      if (is_null($dbConfigFile)) { $dbConfigFile = APP_CONFIGDIR.'/db.conf'; }
      if (is_null($name)) { $name = $this->settings['defaults']['db.name']; }

      $dbConnect = json_decode(base64_decode(file_get_contents($dbConfigFile)),true);

      if (!$dbConnect) { return false; }

      $buildResult = $this->buildClass("db.$name",$className,null,$fileName);

      $this->debug(9,"buildResult:$buildResult for class:$className name:$name");

      if (!$buildResult) { return false; }

      define('DB_SERVER',$dbConnect['hostname']);
      define('DB_USER',$dbConnect['username']);
      define('DB_PASS',$dbConnect['password']);

      $connectResult = $this->db($name)->connect($dbConnect['hostname'],$dbConnect['username'],$dbConnect['password'],$dbConnect['database']);

      $this->debug(9,"connectResult:$connectResult for class:$className name:$name");

      return $connectResult;
   }

   public function buildClass($objName, $className, $options = null, $fileName = null)
   {
      if (!$this->classList[$className] && !$this->autoLoad && !is_null($fileName)) { $this->includeClass($className,$fileName); }

      if (!$this->classList[$className]) {
         $this->debug(9,"Could not load class for $className");
         return false;
      }

      $this->objects[$objName] = new $className($this->debug,$options);

      if (!is_a($this->objects[$objName],$className)) {
         $this->debug(9,"Could not build class object for $className");
         return false;
      }

      return true;
   }

   public function autoLoad($function = 'autoLoader')
   {
      $this->autoLoad = true;
      spl_autoload_register($function);

      $this->debug(9,"Autoload enabled");
   }

   public function autoLoader($className)
   {
      $lcName   = strtolower(basename(str_replace("\\",DIRECTORY_SEPARATOR,$className)));
      $fileName = "$lcName.class.php";

      return $this->includeClass($className,$fileName);
   }

   public function includeClass($className, $fileName)
   {
      $this->debug(8,"called");

      $success = (!@include_once($fileName)) ? false : true;

      $this->debug(9,"Trying to load class $className from file: $fileName (".(($success)?'success':'failure').")");

      if ($success) { $this->classUsed($className,$fileName); }

      return $success;
   }

   public function classUsed($className = null, $fileName = null)
   {
      if (!is_null($className)) { $this->classList[$className] = array('fileName' => $fileName); }

      return $this->classList;
   }

   public function debugLevel($level)
   {
      return $this->debug->level($level);
   }

   public function debugType($type)
   {
      return $this->debug->type($type);
   }

   public function db($name = null)
   {
      if (is_null($name)) { $name = $this->settings['defaults']['db.name']; }

      return $this->obj("db.$name");
   }

   public function obj($name) { return $this->objects[$name]; }
}

?>
