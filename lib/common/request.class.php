<?php

//    Copyright 2009,2010 - Ryan Honeyman
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>
//

//======================================================================================================
// Overview: cURL library for PHP
//======================================================================================================
/* Example:

*/
//======================================================================================================

include_once 'base.class.php';

class Request extends Base
{
   protected $version         = 1.0;
   protected $errors          = array();
   public    $responseCode    = null;
   public    $responseInfo    = null;
   public    $responseHeaders = null;
   public    $responseVerbose = null;
   public    $responseSuccess = null;
   public    $responseBody    = null;
   public    $curlErrorCode   = null;
   public    $curlErrorMesg   = null;

   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);
   }

   public function put($url, $headers = null, $data = null, $options = null)
   {
      $this->debug(8,"called");

      $options['method'] = 'PUT';

      return $this->send($url,$headers,$data,$options);
   }

   public function patch($url, $headers = null, $data = null, $options = null)
   {
      $this->debug(8,"called");

      $options['method'] = 'PATCH';

      return $this->send($url,$headers,$data,$options);
   }

   public function post($url, $headers = null, $data = null, $options = null)
   {
      $this->debug(8,"called");

      $options['method'] = 'POST';

      return $this->send($url,$headers,$data,$options);
   }

   public function delete($url, $headers = null, $options = null)
   {
      $this->debug(8,"called");

      $options['method'] = 'DELETE';

      return $this->send($url,$headers,null,$options);
   }

   public function get($url, $headers = null, $options = null)
   {
      $this->debug(8,"called");

      $options['method'] = 'GET';

      return $this->send($url,$headers,null,$options);
   }

   public function send($url, $headers = null, $data = null, $options = null)
   {
      $method     = strtoupper($options['method']) ?: null;
      $timeout    = $options['timeout'] ?: 15;
      $agent      = $options['agent'] ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
      $referer    = $options['referer'] ?: null;
      $sslVersion = $options['sslversion'] ?: CURL_SSLVERSION_TLSv1;
      $sslCipher  = $options['sslcipher'] ?: null;
      $decode     = $options['decode'] ?: null;
      $expectCode = $options['expectcode'] ?: null;
      $verbose    = ($options['verbose']) ? true : false;
      $follow     = ($options['follow']) ? true : false;

      // If proxy was set, obtain address, username, password
      list($proxyAddr,$proxyUser,$proxyPass) = ($options['proxy']) ?: array(null,null,null);

      // If authentication is provided, get type, username, password
      list($authType,$authUser,$authPass) = ($options['auth']) ?: array(null,null,null);

      $responseHeaders = array();

      $this->curlErrorCode   = null;
      $this->curlErrorMesg   = null;
      $this->responseVerbose = null;

      $this->debug(9,"initialize url($url) timeout($timeout)");

      $curl = curl_init();

      curl_setopt($curl,CURLOPT_URL,$url);

      // Verbose logging
      if ($verbose) {
         curl_setopt($curl,CURLOPT_VERBOSE,true);
         $verboseFile = fopen('php://temp','w+');
         curl_setopt($curl,CURLOPT_STDERR,$verboseFile);
      }

      // HTTPS handling
      if (preg_match('/^https/i',$url)) {
         curl_setopt($curl,CURLOPT_SSLVERSION,$sslVersion);

         if (!is_null($sslCipher)) { curl_setopt($curl,CURLOPT_SSL_CIPHER_LIST,$sslCipher); }

         curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
         curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
      }

      // Proxy Support
      if ($proxyAddr) {
         $this->debug(9,"Proxy requested, setting ".$proxyAddr);
         curl_setopt($curl,CURLOPT_PROXY,$proxyAddr);
         if ($proxyUser) { curl_setopt($curl,CURLOPT_PROXYUSERPWD,sprintf("%s:%s",$proxyUser,$proxyPass)); }
      }

      // How to handle the response headers
      curl_setopt($curl, CURLOPT_HEADERFUNCTION,
         function($curl,$header) use (&$responseHeaders) {
            $len    = strlen($header);
            $header = explode(':',$header,2);

            if (count($header) < 2) return $len; // ignore invalid headers

            $responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);

            return $len;
         }
      );

      curl_setopt($curl,CURLOPT_IPRESOLVE,CURL_IPRESOLVE_V4);
      curl_setopt($curl,CURLOPT_TIMEOUT,$timeout);
      curl_setopt($curl,CURLOPT_FOLLOWLOCATION,$follow);
      curl_setopt($curl,CURLOPT_AUTOREFERER,true);
      curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
      curl_setopt($curl,CURLOPT_USERAGENT,$agent);

      if ($referer) { curl_setopt($curl,CURLOPT_REFERER,$referer); }

      if ($method) {
         $this->debug(7,"Method: $method");
         curl_setopt($curl,CURLOPT_CUSTOMREQUEST,$method);
      }

      if ($authType) {
         $this->debug(9,"Credentials supplied, setting $authType auth parameters");

         $httpAuthList = array(
            'ntlm'  => CURLAUTH_NTLM,
            'basic' => CURLAUTH_BASIC,
         );

         $httpAuthType = $httpAuthList[$authType] ?: $httpAuthList['basic'];

         curl_setopt($curl,CURLOPT_HTTPAUTH,$httpAuthType);
         curl_setopt($curl,CURLOPT_USERPWD,sprintf("%s:%s",$authUser,$userPass));
      }

      if (!is_null($data)) {
         if (!$method) { $method = 'POST'; }

         $postFields = $this->formatBody($headers['Content-Type'],$data);

         curl_setopt($curl,CURLOPT_POSTFIELDS,$postFields);

         $headers['Content-Length'] = strlen($postFields);

         if ($this->debug->level() == 9) {
            // temporary solution to remove specific passwords
            $replaceInfo = array(
               'pattern' => array(
                  '/(password)=\S+?\&/i',
                  '/"(password)":"\S+?"/i'
               ),
               'replacement' => array(
                  '$1=*****&',
                  '"$1":"*****"',
               ),
            );
            $displaystring = preg_replace($replaceInfo['pattern'],$replaceInfo['replacement'],$postFields);

            $this->debug(9,"BODY provided: $displaystring");
         }
      }

      if ($headers) {
         $this->requestHeaders = $headers;
         $httpHeaders          = array();

         foreach ($headers as $headerKey => $headerValue) {
            $httpHeaders[] = "$headerKey: $headerValue";

            if (preg_match('/authorization|authentication/i',$headerKey)) { $headerValue = '*****'; }

            $this->debug(9,"Header> $headerKey: $headerValue");
         }
         curl_setopt($curl,CURLOPT_HTTPHEADER,$httpHeaders);
      }

      $this->debug(8,"sending request");

      $result = curl_exec($curl);

      if ($result === false) {
         $this->curlErrorCode = curl_errno($curl);
         $this->curlErrorMesg = curl_error($curl);

         $this->error(sprintf("CURL Error: (%s) %s",$this->curlErrorCode,$this->curlErrorMesg));

         return false;
      }

      $curlinfo = curl_getinfo($curl);

      curl_close($curl);

      $this->responseInfo    = $curlinfo;
      $this->responseCode    = $curlinfo['http_code'];
      $this->responseHeaders = $responseHeaders;

      // Connection time out
      if ($this->responseCode == 0) {
         $this->error("connection timeout occurred");
         return false;
      }

      //$this->debug(9,"raw: $result");

      // Decode response body is requested to do so
      if ($decode) {
         if (preg_match('/json/i',$decode)) { $this->responseBody = json_decode($result,true); }
      }
      else { $this->responseBody = $result; }

      // Match expected code to response code
      if ($expectCode && !preg_match("/^$expectCode$/",$this->responseCode)) {
         $this->error("did not receive expected code match $expectCode, got ".$this->responseCode);
         return false;
      }

      $resultSize = strlen($result);

      $this->debug(8,"request complete: ".$this->responseCode.", $resultSize bytes read");
      $this->debug(9,"result: ".substr($result,0,256).(($resultSize > 256) ? '...' : ''));

      if ($verbose) {
         rewind($verbosefile);
         $this->responseVerbose = stream_get_contents($verbosefile);
      }

      return true;
   }

   public function response($type = null)
   {
      if (preg_match('/^full$/i',$type)) {
         return array('code' => $this->responseCode, 'requestHeaders' => $this->requestHeaders, 'responseHeaders' => $this->responseHeaders,
                      'result' => $this->responseBody, 'info' => $this->responseInfo);
      }

      return $this->responseBody;
   }

   public function formatBody($type, $data)
   {
      if (preg_match('~^application/json$~i',$type)) {
         return json_encode($data,JSON_UNESCAPED_SLASHES);
      }
      else if (preg_match('~^application/x-www-form-urlencoded$~i',$type)) {
         return http_build_query($data);
      }
      else if (preg_match('~^multipart/form-data$~i',$type)) {
         return $this->multipartEncode($data);
      }
      else if (is_scalar($data) || is_null($data)) { return $data; }

      $this->error('no content type detected for non-scalar request body');

      return false;
   }

   public function multipartEncode($data)
   {
      // TODO
      return $data;
   }

   public function decodeSetCookieHeader($setcookies)
   {
      $return = array();

      if (!is_array($setcookies)) { return $return; }

      foreach ($setcookies as $scId => $scCookie) {
         // Use parse_str after converting the cookie into what would appear to be a URL
         parse_str(strtr($scCookie,array('&' => '%26', '+' => '%2B', ';' => '&')),$cookieData);

         $nameList = array();
         foreach ($cookieData as $cKey => $cValue) {
            if (preg_match('/^(?:expires|max-age|path|domain|secure|httponly)$/i',$cKey)) { continue; }
            $nameList[$cKey] = $cValue;
            unset($cookieData[$cKey]);
         }

         foreach ($nameList as $cookieName => $cookieValue) { $return[$cookieName] = array_merge($cookieData,array('value' => $cookieValue)); }
      }

      return $return;
   }
}

?>
