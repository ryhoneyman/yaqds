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

class AdminLTE extends Base
{
   protected $version = 1.0;
   public    $options = null;

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

   public function displayRow($content)
   {
      return "<div class='row'>\n".
             $content."\n".
             "</div>\n";
   }
   
   public function errorCard($content, $cardProps = null)
   {
      $containerClass = $cardProps['container'] ?: 'col-12 col-xl-3 col-lg-6 col-md-6 col-sm-12';
      $cardClass      = $cardProps['card'] ?: 'card-danger';
      $cardTitle      = $cardProps['title'] ?: 'Error';
      $cardId         = $cardProps['id'] ?: 'errorcard';
   
      return "<div class='$containerClass'>\n".
             "    <div id='$cardId' class='card $cardClass'>\n".
             "       <div class='card-header'><b>$cardTitle</b>\n".
             "          <div class='card-tools'>\n".
             "             <button type='button' class='btn bg-danger btn-sm' data-card-widget='remove'><i class='fas fa-times'></i></button>\n".
             "          </div>\n".
             "       </div>\n".
             "       <div class='card-body'>\n".
             "       ".$content."\n".
             "       </div>\n".
             "   </div>\n".
             "</div>\n";
   }

   public function displayCard($content, $cardProps = null)
   {
      $containerClass = $cardProps['container'] ?: 'col-12 col-xl-3 col-lg-6 col-md-6 col-sm-12';
      $cardClass      = $cardProps['card'] ?: 'card-primary';
      $headerClass    = $cardProps['header'] ?: '';
      $cardTitle      = $cardProps['title'] ?: '';
      $cardId         = $cardProps['id'] ?: 'card';
      $footerClass    = $cardProps['footer'] ?: '';
      $footerContent  = $cardProps['footerContent'] ?: '';

      return "<div class='$containerClass'>\n".
             "    <div id='$cardId' class='card $cardClass'>\n".
             "       <div class='card-header $headerClass'><h3 class='card-title text-bold'>$cardTitle</h3></div>\n".
             "       <div class='card-body'>\n".
             "       ".$content."\n".
             "       </div>\n".
             (($footerContent) ? "       <div class='card-footer $footerClass'>$footerContent</div>\n" : '').
             "   </div>\n".
             "</div>\n";
   }
   
   public function displayTabbedCard($content, $cardProps = null)
   {
      $containerClass = $cardProps['container'] ?: 'col-12 col-xl-3 col-lg-6 col-md-6 col-sm-12';
      $cardClass      = $cardProps['card'] ?: 'card-primary';
      $cardTitle      = $cardProps['title'] ?: '';
      $cardId         = $cardProps['id'] ?: 'tabcard';
      $cardControl    = $cardProps['control'] ?: null;
   
      $headerContent = array();
      $bodyContent   = array();
   
      foreach ($content as $position => $tabInfo) {
         $tabName   = $tabInfo['name'];
         $tabImage  = $tabInfo['image'];
         $tabData   = $tabInfo['data'];
         $tabActive = ($position === 0) ? true : false;

         if ($tabImage) { $tabName = sprintf("<img src='%s' style='height:50px;'>",$tabImage); }
   
         $headerContent[] = "             <li class='nav-item'><a class='nav-link".(($tabActive) ? ' active' : '')."' id='$cardId-$position-tab' ".
                                             "data-toggle='pill' href='#$cardId-$position' role='tab' aria-controls='$cardId-$position' ".
                                             "aria-selected='".(($tabActive) ? 'true' : 'false')."'>$tabName</a></li>\n";
         $bodyContent[]   = "             <div class='tab-pane fade".(($tabActive) ? ' show active' : '')."' id='$cardId-$position' role='tabpanel' ".
                                             "aria-labelledby='$cardId-$position-tab'>$tabData</div>\n";
      }
   
      return "<div class='$containerClass'>\n".
             "    <div class='card $cardClass card-tabs'>\n".
             "       <div class='card-header p-0 pt-1'>\n".
             "          <ul class='nav nav-tabs' id='$cardId' role='tablist'>\n".
             "             <li class='pt-2 px-3'><h3 class='card-title text-bold'>$cardTitle</h3></li>\n".
             implode('',$headerContent).
             (($cardControl) ? $cardControl."\n" : '').
             "          </ul>\n".
             "       </div>\n".
             "       <div class='card-body'>\n".
             "          <div class='tab-content' id='{$cardId}Content'>\n".
             implode('',$bodyContent).
             "          </div>\n".
             "       </div>\n".
             "   </div>\n".
             "</div>\n";
   }

   public function infoBox($label, $link, $description = null, $boxProps = null)
   {
      $containerClass    = $boxProps['container'] ?: 'col-10 col-sm-6 col-md-6 col-lg-6 col-xl-3';
      $boxIcon           = $boxProps['icon'] ?: 'fa-question';
      $boxIconBackground = $boxProps['icon-bg'] ?: 'bg-primary';
      $ribbonLabel       = $boxProps['ribbon'] ?: null;
      $ribbonBackground  = $boxProps['ribbon-bg'] ?: 'bg-danger';
      
      return "<div class='$containerClass'>\n".
             "   <div class='info-box'>\n".
             "      <span class='info-box-icon $boxIconBackground elevation-1'><a href='$link'><i class='fas $boxIcon' aria-hidden='true'></i></a></span>\n".
             "      <div class='ribbon-wrapper'>\n".
             (($ribbonLabel) ? "         <div class='ribbon $ribbonBackground'>$ribbonLabel</div>\n" : '').
             "      </div>\n".
             "      <div class='info-box-content'>\n".
             "         <span class='info-box-text'> <a href='$link'><b>$label</b></a></span>\n".
             "         <span class='info-box-number' style='font-weight:normal;'>\n".
             "            $description\n".
             "         </span>\n".
             "      </div>\n".
             "   </div>\n".
             "</div>\n";
   }

}

?>
