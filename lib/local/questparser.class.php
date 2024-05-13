<?php
// Copyright 2023 - Ryan Honeyman

include_once 'common/base.class.php';

class QuestParser extends LWPLib\Base
{
   protected $version   = 1.0;
   protected $quest     = ''; 
   protected $functions = [];

   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);
   }
 
   public function extractFunctions(): void 
   {
      if (!$this->quest) { return; }

      $functionList = array_filter(preg_replace("~\s*end\s*$~is",'',preg_split('~function\s+~is',$this->quest)));

      foreach ($functionList as $functionText) {
         if (preg_match('~^(\S+)\((.*?)\)(?:\s*``)?\s*(.*)$~i',$functionText,$match)) {
            $functionName   = $match[1];
            $functionParams = preg_split('~[,\s]+~',$match[2]);
            $functionData   = $match[3];
            $functionBody   = [];

            foreach (array_map('trim',explode('``',$functionData)) as $bodyLine) {
               $bodyLine = preg_replace('~--.*$~','',$bodyLine);
               if ($bodyLine) { $functionBody[] = trim($bodyLine); }
            }

            $this->functions[$functionName] = [
               'name'   => $functionName,
               'params' => $functionParams,
               'body'   => $functionBody,
            ];
         }
      }
   }

   public function processFunctions(): void 
   {
      if (!$this->functions) { return; }

      foreach ($this->functions as $functionName => $functionInfo) {
         $this->processFunction($functionName);
      }
   }

   public function processFunction(string $functionName): void 
   {
      if (!isset($this->functions[$functionName]['body'])) { return; }

      foreach ($this->functions[$functionName]['body'] as $line) {
         $this->functions[$functionName]['parsed'][] = ['original' => $line, 'processed' => $this->lexer($line)];
      }
   }

   public function lexer(string $line, $caller = null): mixed
   {
      $tmp = [];

      if (!$line) { return $tmp; }

      print "CALLED: $line (by $caller)\n";

      $lineChars     = str_split($line);
      $buffer        = '';
      $bufferRetiurn = '';
      $escaped       = false;
      $quoted        = false;
      $balancer      = ['\'' => false, '"' => false, '[' => 0, ']' => 0, '{' => 0, '}' => 0, '(' => 0, ')' => 0];
      $stack         = [];

      $pos = -1;

      foreach ($lineChars as $char) {
         $pos++;
         switch($char) {
            case '\\': $escaped = true; continue;
            case '\'': 
            case '"':  
               if (!$escaped) { 
                  if ($balancer[$char] == true) { $balancer[$char] = false; $quoted = false; }
                  else if (!$quoted) { $balancer[$char] = true; $quoted = true; }
               } 
               break;
            case '[':  
            case ']':  
            case '{':  
            case '}':  
            case '(':  
            case ')':  
               if (!$escaped) { $balancer[$char]++; } 
               break;
         }

         if ($char != '\\') { $escaped = false; }

         if ($char == ';') { continue; }

         $buffer .= $bufferReturn.$char;
         $bufferReturn = '';

         //print "QUOTED: ".json_encode($quoted).", BUFFER: $buffer\n";
         //print "TMP:".json_encode($tmp)."\n";

         if (!$quoted) {
            if (preg_match('~^(.*?)([\w\:\.]+)\s*\($~i',$buffer,$match)) {
               $extra    = $match[1];
               $function = $match[2];
               $stack[]  = $function;  
               
               if ($extra) { 
                  if (!preg_match('~^[\'"]~',$extra)) { $extra = array_map('trim',preg_split('~\s*,\s*~',$extra)); }
                  else { $extra = array(trim($extra)); }

                  $newBuffer = [];
                  foreach ($extra as $entry) { 
                     if (!preg_match('~^\s*$~',$entry)) { $newBuffer[] = $entry; }
                  }
                  $extra = $newBuffer;

                  if (!empty($extra)) { 
                     print "EXTRA: ".json_encode($extra)."\n";
                     $tmp[] = $extra; 
                  }
               }

               print "function start $function\n";
            }/*
            else if (preg_match('~\s*([><=]+)([^><=])$~',$buffer,$match)) {
               print "FOUND CONDITIONAL: ".$match[1]." returning ".$match[2]."\n";
               $bufferReturn = $match[2];
               $tmp[]['conditional'] = $match[1];
            }
            else if (preg_match('~^\s*(and|or)(\W)$~i',$buffer,$match)) {
               print "FOUND OPERATOR: ".$match[1]." returning ".$match[2]."\n";
               $bufferReturn = $match[2];
               $tmp[]['operator'] = $match[1];
            }*/
            else if (preg_match('~^(.*)\)\s*$~',$buffer,$match)) {
               $function = array_pop($stack);
               print "function end $function ($caller) body(".json_encode($match[1]).")\n";

               $results = $this->lexer($match[1],$function);

               print "RESULTS: ".json_encode($results)."\n";

               if ($function) {
                  if ($results) { 
                     if (empty($stack)) { $tmp[] = $results; } 
                     else { $tmp[][$function] = $results; }
                  }
               }

               if (empty($stack)) { 
                  $newtmp = array($function => $tmp); 
                  $tmp    = $newtmp;
               }
            } 
            else if (preg_match('~^\s*then$~i',$buffer,$match)) {
               print "if then\n";
               $tmp['then'] = [];
            }
            else if (preg_match('~^end$~i',$buffer,$match)) {
               print "function end $function\n";
            }
            else { continue; }

            $buffer = '';
         }  
      }

      if ($buffer) { 
         print "BUFFER: $buffer\n";
         if (!preg_match('~^[\'"]~',$buffer)) { $buffer = array_map('trim',preg_split('~\s*,\s*~',$buffer)); }
         else { $buffer = array(trim($buffer)); }

         $newBuffer = [];
         foreach ($buffer as $entry) { 
            if (!preg_match('~^\s*$~',$entry)) { $newBuffer[] = $entry; }
         }
         $buffer = $newBuffer;

         if (!empty($buffer)) { 
            print "MAIN: ".json_encode($buffer)."\n";
            $tmp = $buffer; 
         }
      }

      print "RETURN: ".json_encode($tmp)."\n";
      return $tmp;
   }

   public function removeWhitespace(): void
   {
      if (!$this->quest) { return; }

      $this->quest = preg_replace('~[\n\r]~s','``',$this->quest);
      //$this->quest = preg_replace('~\s+(?=(?:[^\'"]*[\'"][^\'"]*[\'"])*[^\'"]*$)~s',' ',$this->quest);
      $this->quest = preg_replace('~\s+~s',' ',$this->quest);
   }

   public function load(string $quest): void 
   {
      $this->clear();

      $this->quest = $quest;

      $this->removeWhitespace();
      $this->extractFunctions();
      $this->processFunctions();
   }

   public function clear(): void 
   {
      $this->quest     = '';
      $this->functions = [];
   }

   public function showFunctions(): array 
   {
      return $this->functions;
   }

   public function showQuest(): string 
   {
      return $this->quest;
   }
}
