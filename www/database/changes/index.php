<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'debugLevel'     => 0,
   'debugType'      => DEBUG_HTML,
   'errorReporting' => false,
   'sessionStart'   => true,
   'memoryLimit'    => '256M',
   'sendHeaders'    => true,
   'dbConfigDir'    => APP_CONFIGDIR,
   'fileDefine'     => APP_CONFIGDIR.'/defines.json',
   'database'       => true,
   'input'          => true,
   'html'           => true,
   'adminlte'       => true,
   'data'           => APP_CONFIGDIR.'/global.json',
));

$input = $main->obj('input');
$html  = $main->obj('html');
$alte  = $main->obj('adminlte');

$main->title('Database Changes');
$main->pageDescription('Perform enriched database differentials between data sets');

$diffOldDate = '20240419';
$diffNewDate = '20240529';

$diff    = json_decode(file_get_contents(sprintf("%s/database/diffs/diff.%s.%s.json",APP_CONFIGDIR,$diffOldDate,$diffNewDate)),true);
$formats = json_decode(file_get_contents('display.format.json'),true);

include 'ui/header.php';

printf("<h4>Enriched database differential between <span class='text-warning'>%s</span> and <span class='text-warning'>%s</span>:</h4><br>",
       date('Y-m-d',strtotime($diffOldDate)),date('Y-m-d',strtotime($diffNewDate)));

foreach ($diff['modifiedTables'] as $tableName => $stateList) {
    if (is_string($formats['table'][$tableName]['default'])) { continue; }

    $tableFormatKeys = array_map(function($value) { return "@$value:warning@"; },$formats['table'][$tableName]['default'] ?: []);
    $tableFormatVals = array_map(function($value) { return '{{'.$value.'}}'; },$formats['table'][$tableName]['default'] ?: []);

    $addsRemoves = [];
    $changes     = [];
    
    foreach (['added','removed'] as $state) {
        $tableRows = $diff['analysis'][$state][$tableName] ?: [];

        foreach ($tableRows as $rowId => $rowData) {
            $badgeColor = ($state == 'removed') ? 'danger' : (($state == 'added') ? 'success' : 'secondary');
            $rowData['STATE']     = sprintf("<small class='badge badge-%s'>%s</small>",$badgeColor,strtoupper($state));
            $rowData['TABLENAME'] = sprintf("<small class='badge badge-warning'>%s</small>",$tableName);

            $addsRemoves[] = displayFormattedData('<tr><td>'.implode('</td><td>',$tableFormatVals).'</td></tr>',$rowData);
        }
    }
    
    $addsRemovesHeader = preg_replace_callback('/@(?<key>\S+?)@/','badgeReplace',"<tr><th>".implode("</td><td>",$tableFormatKeys)."</th></tr>");
    $addsRemovesTable  = ($addsRemoves) ? sprintf("<table class='table table-sm table-striped' border=0>\n%s\n%s</table>",$addsRemovesHeader,implode('',$addsRemoves)) : '';

    $state     = 'changed';
    $tableRows = $diff['analysis'][$state][$tableName] ?: [];

    foreach ($tableRows as $rowId => $rowData) {
        $badgeColor = 'primary';
        $rowData['STATE']     = sprintf("<small class='badge badge-%s'>%s</small>",$badgeColor,strtoupper($state));
        $rowData['TABLENAME'] = sprintf("<small class='badge badge-warning'>%s</small>",$tableName);

        $objectChanges = [];

        foreach ($rowData['after'] as $changeKey => $newValue) {
            $objectChanges[] = sprintf("<small class='badge badge-secondary'>%s</small><small>(%s -> %s)</small>",$changeKey,$rowData['before'][$changeKey],$newValue);
        }

        $rowData['OBJECTCHANGES'] = implode(' ',$objectChanges);

        $changes[] = displayFormattedData("<tr><td>".implode("</td><td>",array_values($formats['default']['changed'] ?: []))."</td></tr>\n",$rowData);
    }

    $changesHeader = preg_replace_callback('/@(?<key>\S+?)@/','badgeReplace',"<tr><th>".implode("</td><td>",array_keys($formats['default']['changed'] ?: []))."</th></tr>\n");
    $changesTable  = ($changes) ? sprintf("<table class='table table-sm table-striped' border=0>\n%s\n%s</table>",$changesHeader,implode('',$changes)) : '';

    print $alte->displayCard($alte->displayRow(
        $addsRemovesTable.$changesTable,
        array('container' => 'col-xl-9 col-12')
    ),array('container' => 'col-xl-9 col-12', 'title' => $tableName, 'card' => 'card-secondary collapsed-card', 'tools' => "<button type='button' class='btn btn-tool' data-card-widget='collapse' data-expand-icon='fa-caret-down' data-collapse-icon='fa-caret-up'><i class='fa fa-caret-down'></i></button>"));
}

include 'ui/footer.php';

?>
<?php

function badgeReplace($matches)
{
    list($value,$badgeColor) = explode(':',$matches['key']);

    if (!$badgeColor) { $badgeColor = 'secondary'; }

    return sprintf("<small class='badge badge-%s'>%s</small>",$badgeColor,$value);
}

function displayFormattedData($format, $values) 
{
   if (!is_null($values) && is_array($values)) {
      $replace = array();
      foreach ($values as $key => $value) { $replace['{{'.$key.'}}'] = ((is_array($value)) ? implode('; ',array_filter(array_unique($value))) : ((is_bool($value)) ? json_encode($value) : $value)); }

      $format = str_replace(array_keys($replace),array_values($replace),$format);
   }

   return $format;
}

?>