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
// V2-2018 : Mrotar updated to use semantic ui elements
//======================================================================================================
// Overview:
//======================================================================================================
/* Example:



*/
//======================================================================================================

include_once 'base.class.php';

class HTML extends Base
{
   protected $version = 1.2;
   protected $tagData = array();

   public function autofill($divname, $autovalues)
   {
      return '<script TYPE="text/javascript">'."\n".
             '$(function() {'."\n".
             '  var availableValues = '.json_encode($autovalues).';'."\n".
             '  $("#'.$divname.'").autocomplete({'."\n".
             '    source: availableValues'."\n".
             '  });'."\n".
             '});'."\n".
             '</script>'."\n";
   }

   public function table($data, $index = array(), $options = array())
   {
      if (!$options['datatable'] && (!is_array($data) || empty($data))) {
         //return "No valid data given to render table.";
      }

      if (empty($index)) {
         $index = array_values(array_slice($data,0,1));
         $index = array_keys($index[0]);
      }

      if (!is_array($index) || empty($index)) { return "Could not detect header columns for table."; }

      $header = array();
      foreach ($index as $name => $value) {
         list($hid,$hval) = (is_int($name)) ? array($value,$value) : array($value,$name);
         $header[$hid] = $hval;
      }

      $tid     = ($options['table.id'])    ? $options['table.id']     : '';
      $tclass  = ($options['table.class']) ? $options['table.class']  :'ui unstackable teal celled striped compact small table';
      $tstyle  = ($options['table.style']) ? $options['table.style']  : '';
      $thclass = ($options['datatable'])   ? " class='cursorpointer'" : '';

      $htrclass = ($options['compatibility.zebra']) ? " class='header'" : '';

      $return = "<table id='$tid' class='$tclass' style='$tstyle'>\n".
                "<thead><tr{$htrclass}>\n";

      if ($options['datatable.drilldown']) { array_unshift($header,''); }

      foreach ($header as $hid => $column) {
         $return .= "<th{$thclass}>$column</th>\n";
      }

      $return .= "</tr></thead>\n<tbody>";

      if (!is_array($data)) { $data = array(); }

      $rowcount = 0;

      foreach ($data as $id => $info) {
         $rowcount++;
         $trid = ($options['tr']['id'][$id]) ? $options['tr']['id'][$id] : '';
         $trstyle = ($options['tr']['style'][$id]) ? $options['tr']['style'][$id] : '';
         $trclass = ($options['tr']['class'][$id]) ?
                     $options['tr']['class'][$id] :
                     (($options['table.tr.class']) ? $options['table.tr.class'] : '');

         if ($options['compatibility.zebra']) { $trclass = ($rowcount % 2 == 0) ? 'even' : 'odd'; }

         if ($trid) { $trid = " id='$trid'"; }
         if ($trclass) { $trclass = " class='$trclass'"; }
         if ($trstyle) { $trstyle = " style='$trstyle'"; }

         $return .= "<tr{$trid}{$trclass}{$trstyle}>\n";

         foreach ($header as $hid => $column) {
            $tdclass = ($options['td']['class']['cell'][$column][$id]) ?
                        $options['td']['class']['cell'][$column][$id] :
                        (($options['td']['class']['column'][$column]) ?
                         $options['td']['class']['column'][$column] :
                         (($options['table.td.class']) ?
                           $options['table.td.class'] : ''));

            $tdstyle = ($options['td']['style']['cell'][$column][$id]) ?
                        $options['td']['style']['cell'][$column][$id] :
                        (($options['td']['style']['column'][$column]) ?
                         $options['td']['style']['column'][$column]:'');

            if ($tdclass) { $tdclass = " class='$tdclass'"; }
            if ($tdstyle) { $tdstyle = " style='$tdstyle'"; }

            $tdval   = $info[$hid];
            $return .= "<td{$tdclass}{$tdstyle}>$tdval</td>\n";
         }

         $return .= "</tr>\n";
      }

      $return .= "</tbody>\n".
                 "</table>\n";

      if ($options['datatable']) {
         // Datatables options available at: http://www.datatables.net/ref
         //===============================================================
         $dtoptions = array(
            'aaSorting'      => '[]',
            'iDisplayLength' => 10,
            'aLengthMenu'    => '[[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]',
         );

         if ($options['datatable.ajax']) {
            $dtoptions['sAjaxSource'] = "'".$options['datatable.ajax']."'";
            $dtoptions['aoColumns'] = "[\n";
            foreach ($header as $hid => $column) {
               $dtoptions['aoColumns'] .= "            { 'mData': '$hid' },\n";
            }
            $dtoptions['aoColumns'] .= "      ]";
         }

         if ($options['datatable.drilldown']) {
            $dtoptions['bProcessing'] = 'true';
            $dtoptions['aoColumns'] =
            "[\n".
            "            {\n".
            "               'mData': null,\n".
            "               'sClass': 'control',\n".
            "               'sDefaultContent': '<img src=\"'+sImageUrl+'details_open.png'+'\">'\n".
            "            },\n";

            foreach ($header as $hid => $column) {
               if (!$hid) { continue; }
               $dtoptions['aoColumns'] .= "            { 'mData': '$hid' },\n";
            }

            $dtoptions['aoColumns'] .= "      ]";
         }

         if (is_array($options['datatable.options'])) {
            foreach ($options['datatable.options'] as $name => $value) {
               $dtoptions[$name] = $value;
            }
         }

         $optstring = "";
         foreach ($dtoptions as $name => $value) {
            $optstring .= "      '$name': $value,\n";
         }

         $extradt = "";
         if ($options['datatable.drilldown']) {
            $drillitems = $options['datatable.drilldown'];

            $extradt =  "$('#$tid').on('click', 'td.control', function () {\n".
                        "   var nTr = this.parentNode;\n".
                        "   var i = $.inArray( nTr, anOpen );\n\n".
                        "   if ( i === -1 ) {\n".
                        "      $('img', this).attr( 'src', sImageUrl+'details_close.png' );\n".
                        "      var nDetailsRow = oTable.fnOpen( nTr, fnFormatDetails(oTable, nTr), ".
                                                              "'details' );\n".
                        "      $('div.innerDetails', nDetailsRow).slideDown();\n".
                        "      anOpen.push( nTr );\n".
                        "   }\n".
                        "   else {\n".
                        "         $('img', this).attr( 'src', sImageUrl+'details_open.png' );\n".
                        "         $('div.innerDetails', $(nTr).next()[0]).slideUp( function () {\n".
                        "            oTable.fnClose( nTr );\n".
                        "            anOpen.splice( i, 1 );\n".
                        "         });\n".
                        "   }\n".
                        "});\n\n".
                        "function fnFormatDetails( oTable, nTr )\n".
                        "{\n".
                        "  var oData = oTable.fnGetData( nTr );\n".
                        "  var sOut =\n".
                        "    '<div class=\"innerDetails\">'+\n".
                        "      '<table cellpadding=5 cellspacing=0 border=0 ".
                                      "style=\"margin-left:5%;\">'+\n";

                        foreach ($drillitems as $column => $did) {
                           $extradt .= "        '<tr><td>$column</td><td>'+oData.$did+'</td></tr>'+\n";
                        }

            $extradt .= "      '</table>'+\n".
                        "    '</div>';\n".
                        "  return sOut;\n".
                        "}\n";


         }

         $filter = "";
         if ($options['datatable.filter']) {
            $filteropts = array(
               'sPlaceHolder' => '"head:after"',
            );
            if (is_array($options['datatable.filter.options'])) {
               foreach ($options['datatable.filter.options'] as $name => $value) {
                  $filteropts[$name] = $value;
               }
            }

            $filter = ".columnFilter({";
            foreach ($filteropts as $name => $value) {
               $filter .= "'$name': $value,";
            }
            $filter .= "})";
         }

         $return .= "<script>\n".
                    "var anOpen = [];\n".
                    "var sImageUrl = '/images/jquery/datatables/';\n".
                    "var oTable;\n\n".
                    "$(document).ready(function() {\n".
                    "   oTable = $('#$tid').dataTable({\n".
                    "$optstring".
                    "   })$filter;\n".
                    "});\n".
                    "$extradt\n".
                    "</script>\n";

         if ($options['datatable.drilldown']) {
            $return .= "<style>div.innerDetails { display: none }</style>\n";
         }
      }

      return $return;
   }

   public function divBox($name, $content = null, $options = null)
   {

      if($options) {

         $background   = ($options['background']) ? $options['background'] : 'ffffff';
         $width        = ($options['width']) ? $options['width']."px" : 'auto';
         $display      = ($options['display']) ? $options['display'] : 'inline-block';
         $bordercolor  = ($options['border.color']) ? $options['border.color'] : '000000';
         $bordersize   = ($options['border.size']) ? $options['border.size'] : '1';
         $padding      = ($options['padding']) ? $options['padding'] : '10';
         $borderradius = ($options['border.radius']) ? $options['border.radius'] : '5';

         return "<div id='$name' class='ui raised segment' style='background-color: #$background; width: $width; ".
                  "display: $display; ".
                  "border: ${bordersize}px #${bordercolor} solid; padding: ${padding}px; ".
                  "-moz-border-radius: ${borderradius}px; -webkit-border-radius: ${borderradius}px; ".
                  "border-radius: ${borderradius}px;'>$content</div>\n";

       }

      else return "<div id='$name' class='ui raised padded segment'>$content</div>\n";

   }

   public function startForm($properties = null)
   {
      $form   = "<form%s>\n";
      $fields = "";

      if (is_array($properties) && !empty($properties)) {
         $fieldlist = array();
         foreach ($properties as $name => $value) {
            $fieldlist[] = "$name='$value'";
         }
         $fields = ' '.implode(' ',$fieldlist);
      }
      $html = sprintf($form,$fields);

      return $html;
   }

   public function endForm()
   {
      return "</form>\n";
   }

   public function submitButton($name, $value, $label = null, $params = null)
   {
      if (!is_array($params)) { $params = array(); }

      if (is_null($label)) { $label = $value; }

      $params['name']  = $name;
      $params['value'] = $value;
      
      if (!$params['type'])  { $params['type']  = 'submit'; }
      if (!$params['class']) { $params['class'] = 'btn-wide btn btn-primary'; }

      $paramList = array();

      foreach ($params as $paramKey => $paramValue) { $paramList[] = "$paramKey='$paramValue'"; }

      return sprintf("<button %s>%s</button>",implode(' ',$paramList),$label);
   }

   public function submit($name = 'submit', $value = 'Go', $values = null)
   {
      if (!is_array($values)) { $values = array(); }

      $values['name']  = $name;
      $values['value'] = $value;
 
      if (!$values['type'])  { $values['type']  = 'submit'; }
      if (!$values['class']) { $values['class'] = 'btn-wide btn btn-primary'; }

      $valueList = array();

      foreach ($values as $k => $v) { $valueList[] = "$k='$v'"; }

      return sprintf("<input %s>",implode(' ',$valueList));
   }

   public function reset($name = 'reset', $value = 'Clear', $values = null)
   {
      if (!is_array($values)) { $values = array(); }

      $values['name']  = $name;
      $values['value'] = $value;

      if (!$values['type'])  { $values['type']  = 'reset'; }
      if (!$values['class']) { $values['class'] = 'btn-wide btn btn-primary'; }

      $valueList = array();

      foreach ($values as $k => $v) { $valueList[] = "$k='$v'"; }

      return sprintf("<input %s>",implode(' ',$valueList));
   }

   public function inputTextarea($name, $text='', $cols=30, $rows=5, $options = null)
   {
      $this->debug(5,"name=$name, text=$text, cols=$cols, rows=$rows");

      $placeholder = ($options['placeholder']) ? $options['placeholder'] : $name;
      $disabled    = ($options['disabled']) ? 'disabled' : '';

      $html = "<textarea $disabled id='$name' name='$name' placeholder='$placeholder' cols='$cols' rows='$rows'>$text</textarea>\n";

      return $html;
   }

   public function inputCalendar($name, $text='',$options = null, $mindate = 'null', $maxdate = 'null', $size=10)
   {
      $this->debug(5,"name=$name, text=$text, dt=$mindate/$maxdate, size=$size");

      if($options) {
         $magnitude = $options['magnitude']?$options['magnitude']:'';
      }

      $html =  "<item class='ui $magnitude input'><input type=text id='$name' name='$name' value='$text' placeholder='$name'>\n".
                "<script>$('#$name').datepicker({minDate: $mindate, maxDate:$maxdate})</script>\n".
                "</item>";

      return $html;
   }

   public function inputText($name, $text='', $maxlength=2048, $size=null, $options = null)
   {
      $this->debug(5,"name=$name, text=$text, maxlength=$maxlength, size=$size");

      $error     = ($options['error']) ? 'error' : '';
      $style     = ($options['style']) ? $options['style'] : '';
      $class     = ($options['class']) ? $options['class'] : 'form-control';
      $disabled  = ($options['disabled']) ? 'disabled' : '';
      $required  = ($options['required']) ? 'required="required"' : '';
      $autocomplete  = ($options['autocomplete']) ? 'autocomplete="'.$options['autocomplete'].'"' : '';
      $onchange = ($options['onchange']) ? 'onchange="'.$options['onchange'].'"' : '';

      $placeholder = ($options['placeholder']) ? $options['placeholder'] : '';

      $html = "<input type=text id='$name' name='$name' $required $autocomplete value='$text' class='$class $disabled' style='$style'.
                      placeholder='$placeholder' maxlength='$maxlength' $onchange ".((!is_null($size)) ? "size='$size'" : '').">\n";

      return $html;
   }

   public function inputFile($name)
   {
      $this->debug(5,"name=$name");

      $html = "<item class='ui input'><input type=file id='$name' name='$name'></item>\n";

      return $html;
   }

   public function inputHidden($name, $value)
   {
      $html = "<item class='ui input'><input type=hidden id='$name' name='$name' value='$value'></item>\n";

      return $html;
   }

   function select($name, $values, $select = array(), $attrib = array())
   {
      $size     = ($attrib['size'])     ? $attrib['size']       : 1;
      $multi    = ($attrib['multi'])    ? $attrib['multi']      : 0;
      $script   = ($attrib['script'])   ? $attrib['script']     : '';
      $assoc    = ($attrib['assoc'])    ? $attrib['assoc']      : 0;
      $style    = ($attrib['style'])    ? $attrib['style']      : '';
      $class    = ($attrib['class'])    ? $attrib['class']      : 'form-control';
      $disabled = ($attrib['disabled']) ? 'disabled'            : '';
      $required = ($attrib['required']) ? 'required="required"' : '';
      $keyopts  = ($attrib['keyopts'])  ? $attrib['keyopts']    : array();
      $data     = ($attrib['data'])     ? $attrib['data']       : array();

      $placeholder = ($attrib['placeholder']) ? $attrib['placeholder'] : '';

      if (isset($select)) {
         if (is_array($select)) {
            foreach ($select as $item) { $selected[$item] = 1; }
         }
         else { $selected[$select] = 1; }
      }

      if (!$this->isAssoc($values) && !$assoc) {
         foreach ($values as $item) { $options[$item] = $item; }
      }
      else { $options = $values; }

      if (!is_array($options)) { $options = array(); }

      $html = sprintf("<select $disabled $required class='$class' id='%s' style='$style' name='%s%s' size='%s'%s%s>\n",
                      $name,$name,($multi) ? "[]" : '',$size,($multi) ? " multiple" : '',
                      ($script) ? " $script" : '');

      foreach ($options as $key => $value) {
         if (is_array($value)) {
            $html .= sprintf('<optgroup label="%s">'."\n",$key);
            foreach ($value as $gkey => $gvalue) {
               //$disabled     = (isset($keyopts['disabled'][$gkey])) ? true : false;
               //$disabledText = ($disabled) ? $keyopts['disabled'][$gkey] : '';

               if (!$gvalue) { $gvalue = $gkey; }
             
               $dataValues = $data[$gkey];
               $dataList   = array();
 
               if ($dataValues) {
                  foreach ($dataValues as $dataKey => $dataValue) {
                     $dataList[] = "data-$dataKey='$dataValue'";
                  }
               }

               $html .= sprintf('<option value="%s"%s%s>%s</option>'."\n",
                                $gkey,
                                ((isset($selected[$gkey])) ? " selected" : ""),
                                (($dataList) ? ' '.implode(' ',$dataList) : ''),
                                $gvalue);
            }
            $html .= "</optgroup>\n";
         }
         else {
            $disabled     = (isset($keyopts['disabled'][$key])) ? true : false;
            $disabledText = ($disabled) ? $keyopts['disabled'][$key] : '';

            $dataValues = $data[$key];
            $dataList   = array();

            if ($dataValues) {
               foreach ($dataValues as $dataKey => $dataValue) {
                  $dataList[] = "data-$dataKey='$dataValue'";
               }
            }

            $html .= sprintf("<option value='%s'%s%s%s>%s</option>\n",
                             $key,
                             ((isset($selected[$key])) ? " selected" : ""),
                             ($disabled) ? " disabled" : "",
                             (($dataList) ? ' '.implode(' ',$dataList) : ''),
                             $value.(($disabled) ? $disabledText : ''));
         }
      }

      $html .= "</select>\n";


      return $html;
   }

   public function checkBox($name, $value = 1, $checked = '')
   {
      $ischecked = 0;

      if (is_array($checked)) {
         foreach ($checked as $thischeck) {
            if ($thischeck == $value) {
               $ischecked = 1;
               break;
            }
         }
      }
      else if ($checked == $value) { $ischecked = 1; }

      return "<input type=checkbox class='ui checkbox'  id='$name' name='$name' value='$value'".
             (($ischecked) ? ' checked' : '').">\n";
   }

   public function set($key,$value) {
      if (isset($key)) { $this->tagData[$key] = $value; }
   }

   public function get($key) {
      if (isset($key)) { return $this->tagData[$key]; }
   }

   public function clear($key) {
      if (isset($key)) { unset($this->tagData[$key]); }
   }

   public function clearall() { unset($this->tagData); }

   public function dynamictags($data)
   {
      $regex = '/\<dynamic\s+name\s*=\s*[\'"](\S+?)[\'"]\>/i';

      if (is_array($data)) {
         return $this->get($data[1]);
      }

      while (preg_match($regex,$data)) {
         $data = preg_replace_callback($regex,array(&$this,'dynamictags'),$data);
      }

      return $data;
   }

   public function parse($html) {
      if (!is_array($html)) {
         if (file_exists($html)) { $html = file($html); }
         else { return; }
      }

      $return = "";

      foreach ($html as $line) {
         $return .= $this->dynamictags($line);
      }

      return $return;
   }

   public function updateText($id, $text)
   {
      return "<script type='text/javascript'>\n".
             "document.getElementById('$id').innerHTML = '$text';\n</script>\n";
   }
}

?>
