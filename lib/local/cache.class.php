<?php

include_once 'common/base.class.php';

class Cache extends LWPLib\Base
{
   protected $version   = 1.0;
   protected $cacheDir  = null;
   public    $cacheUsed = null;

   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);

      $this->cacheDir = $options['cacheDir'] ?? APP_CACHEDIR;
   }
    
   /**
    * readFile
    *
    * @param  mixed $fileName File name to read
    * @param  mixed $options  Optional options to read
    * @return mixed           File data or false on failure
    */
   public function readFile(string $fileName, ?array $options = null): mixed
   {
      $this->error();  // clear any previous error

      $this->cacheUsed = false;

      $filePath = $this->getFilePath($fileName);

      if ($filePath === false) { $this->error("invalid filepath for $fileName"); return false; }

      if (!file_exists($filePath)) { $this->error("file does not exist $filePath"); return false; }

      $jsonData = file_get_contents($filePath);

      if (!$jsonData) { $this->error("could not read contents of $filePath"); return false; }

      $fileData = json_decode($jsonData,true);

      if (!$fileData) { $this->error("invalid json data in $filePath"); return false; }

      if (isset($fileData['info'])) {
         if ($options['onlyIfVersionMatch']) {
            $currentVersion = $fileData['info']['version'] ?? null;
            if (!$currentVersion || ($currentVersion!= $options['onlyIfVersionMatch'])) {
               $this->error("version mismatch for $fileName: {$currentVersion} != {$options['onlyIfVersionMatch']}");
               return false;
            }
         }
      }

      $this->cacheUsed = true;

      return $fileData['data'];
   }
   
   /**
    * writeFile
    *
    * @param  string $fileName File name to write
    * @param  mixed $data      Data to write
    * @param  ?array $info     Optional information to write
    * @return bool             True on success, false on failure
    */
   public function writeFile(string $fileName, mixed $data, ?array $info = null): bool
   {
      $this->error();  // clear any previous error

      if (is_null($data)) { $this->error('no data provided'); return false; }

      $info['updateTs']   = time();
      $info['updateDate'] = date('Y-m-d H:i:s');

      $jsonData = json_encode(['info' => $info, 'data' => $data],JSON_PRETTY_PRINT);
      $filePath = $this->getFilePath($fileName);

      if ($filePath === false) { $this->error("invalid filepath for $fileName"); return false; }

      $writeResult = file_put_contents($filePath,$jsonData);

      if ($writeResult === false) { $this->error("could not write to $filePath"); return false; }

      return true;
   }
   
   /**
    * deleteFile
    *
    * @param  mixed $fileName File name to delete
    * @return bool            True on success, false on failure
    */
   public function deleteFile(string $fileName): bool
   {
      $filePath = $this->getFilePath($fileName);

      if ($filePath === false) { return false; }

      if (!file_exists($filePath)) { return false; }

      return unlink($fileName);
   }
   
   /**
    * getFilePath
    *
    * @param  mixed $fileName File name to get
    * @param  mixed $options  Optional options to get
    * @return string|bool     File path or false on failure
    */
   public function getFilePath(string $fileName, ?array $options = null): string|bool
   {
      if (!$fileName) { return false; }

      return $this->cacheDir.'/'.$fileName.'.json';
   }
}
