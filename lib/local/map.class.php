<?php

//    Copyright 2023 - Ryan Honeyman

include_once 'common/base.class.php';

class Map extends Base
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
   }

   function generateSVGMap($zoneName, $zoneFloor = null, $zoneCeil = null, $options = null)
   {
      $mapRawData = file_get_contents(APP_CONFIGDIR."/maps/$zoneName.txt");
      $mapData    = explode("\n",$mapRawData);
   
      $svgLayers = array('defs' => array(), 'active' => array(), 'inactive' => array(), 'gridLine' => array(), 'gridNum' => array());
      $svgProp   = array('minX' => 0, 'maxX' => 0, 'minY' => 0, 'maxY' => 0);
   
      $strokeWidth = $options['strokeWidth'] ?: 3;
      $markUnits   = $options['markUnits'] ?: 100;
      $svgDefs     = $options['defs'] ?: null;

      if ($svgDefs) {
         if (!is_array($svgDefs)) { $svgDefs = array($svgDefs); }
         $svgLayers['defs'] = array_merge(array('<defs>'),$svgDefs,array('</defs>'));
      }
   
      foreach ($mapData as $dataLine) {
         if (preg_match('/^L\s+(.*)$/i',$dataLine,$match)) {
            $lineAttribs = explode(',',preg_replace('/\s/','',$match[1]));

            $lineX1 = $lineAttribs[0];
            $lineY1 = $lineAttribs[1];
            $lineZ1 = $lineAttribs[2];
            $lineX2 = $lineAttribs[3];
            $lineY2 = $lineAttribs[4];
            $lineZ2 = $lineAttribs[5];
            $lineR  = $lineAttribs[6];
            $lineG  = $lineAttribs[7];
            $lineB  = $lineAttribs[8];
   
            $layer = 'active';

            if ((!is_null($zoneFloor) && ($lineZ1 < $zoneFloor || $lineZ2 < $zoneFloor)) ||
                (!is_null($zoneCeil) && ($lineZ1 > $zoneCeil || $lineZ2 > $zoneCeil))) { $lineR = 245; $lineG = 245; $lineB = 245; $layer = 'inactive'; }
   
            if ($lineX1 > $svgProp['maxX']) { $svgProp['maxX'] = $lineX1; }
            if ($lineX2 > $svgProp['maxX']) { $svgProp['maxX'] = $lineX2; }
   
            if ($lineX1 < $svgProp['minX']) { $svgProp['minX'] = $lineX1; }
            if ($lineX2 < $svgProp['minX']) { $svgProp['minX'] = $lineX2; }
   
            if ($lineY1 > $svgProp['maxY']) { $svgProp['maxY'] = $lineY1; }
            if ($lineY2 > $svgProp['maxY']) { $svgProp['maxY'] = $lineY2; }
   
            if ($lineY1 < $svgProp['minY']) { $svgProp['minY'] = $lineY1; }
            if ($lineY2 < $svgProp['minY']) { $svgProp['minY'] = $lineY2; }
   
            $svgLayers[$layer][] = sprintf("<line x1='%d' y1='%d' x2='%d' y2='%d' stroke='rgb(%d,%d,%d)' stroke-width='%d'/>\n",
                                           $lineX1,$lineY1,$lineX2,$lineY2,$lineR,$lineG,$lineB,$strokeWidth);
         }
      }
   
      $svgWidth  = $svgProp['maxX'] - $svgProp['minX'];
      $svgHeight = $svgProp['maxY'] - $svgProp['minY'];
   
      $startXAt  = $svgProp['minX'] + abs($svgProp['minX'] % $markUnits);
      $startYAt  = $svgProp['minY'] + abs($svgProp['minY'] % $markUnits);
   
      $xMark = $startXAt;
      while ($xMark < $svgProp['maxX']) {
         $svgLayers['gridLine'][] = sprintf("<line x1='%d' y1='%d' x2='%d' y2='%d' stroke='rgb(%d,%d,%d)' stroke-width='%d'/>\n",$xMark,$svgProp['minY'],$xMark,$svgProp['maxY'],200,200,200,2);
         $svgLayers['gridNum'][]  = sprintf("<text x=%d y=%d fill='grey' style='font-weight:bold;font-size:20px;'>%d</text>",$xMark+5,$svgProp['minY']+20,$xMark);
         $xMark += $markUnits;
      }
   
      $yMark = $startYAt;
      while ($yMark < $svgProp['maxY']) {
         $svgLayers['gridLine'][] = sprintf("<line x1='%d' y1='%d' x2='%d' y2='%d' stroke='rgb(%d,%d,%d)' stroke-width='%d'/>\n",$svgProp['minX'],$yMark,$svgProp['maxX'],$yMark,200,200,200,2);
         $svgLayers['gridNum'][]  = sprintf("<text x=%d y=%d fill='grey' style='font-weight:bold;font-size:20px;'>%d</text>",$svgProp['maxX']+0,$yMark-5,$yMark);
         $yMark += $markUnits;
      }

      $svgLayers['active'][] = "<circle r=10 cx=0 cy=0 stroke='red' stroke-width=5 fill='white'/>";
   
      $svg = array_merge(
         array(sprintf("<svg id='svg' class='svg' width=%d height=%d viewBox='%d %d %d %d' xmlns='http://www.w3.org/2000/svg'>\n",
                       $svgWidth,$svgHeight,$svgProp['minX'],$svgProp['minY'],$svgWidth,$svgHeight)),
         $svgLayers['defs'],
         $svgLayers['inactive'],
         $svgLayers['gridLine'],
         $svgLayers['gridNum'],
         $svgLayers['active'],
         array("</svg>"));
   
      return $svg;
   }

   public function getXYFromHeading($radius, $headingDegree)
   {
      $radians = (360 - $headingDegree + 90) * pi()/180;

      return array(
         'x' => $radius * cos($radians),
         'y' => -$radius * sin($radians),
      );
   }
}
?>
