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
// Overview:
//======================================================================================================
/* Example:


*/
//======================================================================================================

include_once 'base.class.php';

class Mailer extends Base
{
   protected $version  = 1.0;
   protected $smtp     = null;
   protected $data     = array();
   protected $crlf     = PHP_EOL;
   protected $charset  = 'UTF-8';

   //===================================================================================================
   // Description: Creates the class object
   // Input: object(debug), Debug object created from debug.class
   // Input: array(options), List of options to set in the class
   // Output: null()
   //===================================================================================================
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);
   }

   public function mailServer($serverName, $serverPort, $serverCrypto = null, $allowInecure = null)
   {
      $this->dataValue('serverName',$serverName);
      $this->dataValue('serverPort',$serverPort);
      $this->dataValue('serverCrypto',$serverCrypto);
      $this->dataValue('allowInsecure',$allowInsecure);
   }

   public function mailAuth($username, $password)
   {
      $this->dataValue('username',$username);
      $this->dataValue('password',$password);
   }

   public function mailHeader($headerName, $headerValue)
   {
      $this->dataValue('headers',array_merge($this->dataValue('headers') ?: array(),array($headerName => $headerValue)));
   }

   public function mailSend()
   {
      $this->createMessage();
       
      $this->smtpConnect($this->dataValue('serverName'),
                         $this->dataValue('serverPort'),
                         $this->dataValue('serverCrypto'));
   }

   public function mailFrom($fromEmail, $fromName = null)
   {
      $this->dataValue('From',$this->formatEmail($fromEmail,$fromName));
      $this->dataValue('Return-Path',$fromEmail);
   }

   public function mailReplyTo($replyEmail, $replyName = null) 
   { 
      $this->dataValue('Reply-To',$this->formatEmail($fromEmail,$fromName));
   }

   public function mailToList($toList)
   {
      foreach ($toList as $toEmail => $toName) { $this->mailTo($toEmail,$toName); } 
   }

   public function mailTo($toEmail, $toName = null)
   {
      $this->dataValue('To',array_merge($this->dataValue('To') ?: array(),array($this->formatEmail($fromEmail,$fromName))));
   }

   public function mailCcList($ccList)
   {
      foreach ($ccList as $ccEmail => $ccName) { $this->mailCc($ccEmail,$ccName); } 
   }

   public function mailCc($ccEmail, $ccName = null)
   {
      $this->dataValue('Cc',array_merge($this->dataValue('To') ?: array(),array($this->formatEmail($ccEmail,$ccName))));
   }

   public function mailBccList($bccList)
   {
      foreach ($bccList as $bccEmail => $bccName) { $this->mailBcc($bccEmail,$bccName); } 
   }

   public function mailBcc($bccEmail, $bccName = null)
   {
      $this->dataValue('Bcc',array_merge($this->dataValue('Bcc') ?: array(),array($this->formatEmail($bccEmail,$bccName))));
   }

   public function mailSubject($subject)
   {
      $this->dataValue('Subject',$subject);
   }

   public function mailBody($body) 
   { 
      $this->dataValue('Body',$body);
   }

   public function mailAttachment($attachType, $attachmentName, $attachContents)
   {
      $this->dataValue('Attachment',array_merge($this->dataValue('Attachment') ?: array(),array('type' => $attachType, 'name' => $attachName, 'contents' => $attachContents)));
   }

   public function formatEmail($email, $name = null)
   {
      return sprintf("%s<%s>",(($name) ? sprintf("=?utf-8?B?%s?= ",$name) : ''),$email);
   }
   
   public function createMessage()
   {
      $headers = array(
         'Date'         => date('r'),
         'MIME-Version' => '1.0',
      );

      foreach (array('From','To','Cc','Bcc','Reply-To','Subject') as $headerName) {
         $dataValue   = $this->dataValue($headerName) ?: '';
         $headerValue = is_array($dataValue) ? implode(', ',$dataValue) : $dataValue;

         $headers[$headerName] = $headerValue;
      }

      $headerContent = implode($this->crlf,array_map(function($key,$value) { return "$key: $value"; }));
      $bodyChunked   = chunk_split(base64_encode($this->dataValue('Body')));

      $bodyContent = "Content-Type: multipart/alternative; boundary=\"{$boundaryAlternative}\"".$this->crlf.$this->crlf.
                     "--{$boundaryAlternative}".$this->crlf.
                     "Content-Type : text/plain; charset=\"{$this->charset}\"".$this->crlf.
                     "Content-Transfer-Encoding: base64".$this->crlf.$this->crlf.
                     $bodyChunked.$this->crlf.$this->crlf.
                     "--{$boundaryAlternative}".$this->crlf.
                     "Content-Type: text/html; charset=\"{$this->charset}\"".$this->crlf.
                     "Content-Transfer-Encoding: base64".$this->crlf.$this->crlf.
                     $bodyChunked.$this->crlf.$this->crlf.
                     "--{$boundaryAlternative}--".$this->crlf;

      if (is_array($this->dataValue('Attachment'))) {
         $attachmentPart = $this->crlf.$this->crlf.
                           "--{$boundaryMixed}".$this->crlf.
                           $bodyContent;
 
         foreach ($this->dataValue('Attachement') as $attachmentInfo) {
            $attachmentType     = $attachmentInfo['type'] ?: 'application/octet-stream';
            $attachmentName     = $attachmentInfo['name'];
            $attachmentContents = $attachmentInfo['contents'];
            $attachmentChunked  = chunk_split(base64_encode($attachmentContents));
           
            $attachmentPart .= $this->crlf.
                               "--{$boundaryMixed}".$this->crlf.
                               "Content-Type: $attachmentType; name=\"{$attachmentName}\"".$this->crlf.
                               "Content-Transfer-Encoding: base64".$this->crlf.
                               "Content-Disposition: attachment; filename=\"{$attachmentName}\"".$this->crlf.$this->crlf;
         }

         $attachmentPart .= $this->crlf.$this->crlf.
                            "--{$boundaryMixed}--".$this->crlf;

         $bodyContent = $attachmentPart;
      }

      $this->dataValue('Message',$headerContent.$this->crlf.
                                 $bodyContent.$this->crlf.$this->crlf.
                                 '.'.$this->crlf);
   }

   public function smtpConnect($serverName, $serverPort, $serverCrypto = null, $allowInsecure = null)
   {
      $target  = sprintf("%s:%d",$serverName,$serverPort);
      $context = null;

      if ($serverCrypto) { $target = sprintf("ssl://%s",$target); }

      if ($allowInsecure) {
         $context = stream_context_create(
            array('ssl' => array('security_level'   => 0,
                                 'verify_peer'      => false,
                                 'verify_peer_name' => false))
         );
      }

      if (($smtpFp = stream_socket_client($target,$errorCode,$errorMessage,null,STREAM_CLIENT_CONNECT,$context)) === false) {
         $this->error("SMTP connect failed: ($errorCode) $errorMessage"); return false; 
      }

      $this->smtp = $smtpFp;
       
      // We don't actually send a command, just read the stream once it's opened
      if ($this->smtpSendCommand('',220,"Bad SMTP server response") === false) { return false; }

      return true;
   }

   public function smtpStartTls($serverCrypto)
   {
      $cryptoMethods = array(
         ''        => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
         'tlsv1.0' => STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT,
         'tlsv1.1' => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
         'tlsv1.2' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
      );

      $cryptoMethod = $cryptoMethods[strtolower($serverCrypto)] ?: $cryptoMethods[''];

      if (!stream_socket_enable_crypto($this->smtp,true,$cryptoMethod)) {
         $this->error("SMTP start TLS failed: could not enable crypto");
         return false;
      }

      return true;
   }

   public function smtpEhlo($serverName)
   {
      return $this->smtpSendCommand("EHLO $serverName".$this->crlf,250,"EHLO exception");
   }

   public function smtpAuthLogin($username, $password)
   {
      if (($authResult = $this->smtpSendCommand("AUTH LOGIN".$this.crlf,334,"AUTH LOGIN start exception")) === false) { return false; }
      if (($userResult = $this->smtpSendCommand($username.$this.crlf,334,"AUTH LOGIN username exception")) === false) { return false; }
      
      return $this->smtpSendCommand($password.$this->crlf,235,"AUTH LOGIN password exception");
  }

   public function smtpAuthOAuthBearer($sender, $serverName, $serverPort, $token)
   {
      $authCommand = base64_encode(sprintf("n,a=%s,%shost=%s%sport=%s%sauth=Bearer %s%s%s",$sender,chr(1),$serverName,chr(1),$serverPort,chr(1),$token,chr(1),chr(1)));

      return $this->smtpSendCommand("AUTH OAUTHBEARER $authCommand".$this->crlf,235,"OAUTHBEARER exception");
   }

   public function smtpAuthXOAuth($sender, $token)
   {
      $authCommand = base64_encode(sprintf("user=%s%sauth=Bearer %s%s%s",$sender,chr(1),$token,chr(1),chr(1)));

      return $this->smtpSendCommand("AUTH XOAUTH2 $authCommand".$this->crlf,235,"XOAUTH2 exception");
   }

   public function smtpMailFrom($sender)
   {
      return $this->smtpSendCommand("MAIL FROM:<$sender>".$this->crlf,250,"MAIL FROM exception, sender failure");
   }

   public function smtpRcptTo($recipients)
   {
      if (!is_array($receipients)) { $this->error("Invalid recipients"); return false; }

      foreach ($recipients as $recipient) {
         if ($this->smtpSendCommand("RCPT TO:<$recipient>".$this->crlf,250,"RCPT TO exception, recipient failure") === false) { return false; }
      }

      return true;
   }

   public function smtpData($data)
   {
      if (($startResult = $this->smtpSendCommand("DATA".$this->crlf,354,"DATA start exception")) === false) { return false; }

      return $this->smtpSendCommand($data,250,"DATA send exception");
   }

   public function smtpQuit() { return $this->smtpSendCommand("QUIT".$this->crlf,221,"QUIT exception"); }

   public function smtpSendCommand($command, $expectedCode = null, $errorMessage = null)
   {
      if (!$this->smtp) { $this->error("SMTP not connected"); return false; }

      if ($command) { fputs($this->smtp,$command,strlen($comand)); }

      $response = fgets($this->smtp);

      if (preg_match('/^(\d{3})\ /',$response,$match)) { 
         $resultCode = $match[1];

         if (!is_null($expectedCode) && $resultCode != $expectedCode) { 
            $this->error(sprintf("%sexpected:%s,received:%s - %s",($errorMessage) ? "($errorMessage) ": '',$expectedCode,$resultCode,$response));
            return false;
         }

         return $resultCode;
      }

      $this->error("Invalid response received: $response");

      return false; 
   }

   public function dataValue($name, $value = null, $clear = false)
   {
      if (!is_null($value) || $clear) { $this->data[$name] = $value; }

      return $this->data[$name];
   }
}

?>
