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

$main->title('TAKP Database Changes');
$main->pageDescription('Perform enriched database differentials between data sets');

$diffValue = $input->get('diff','numeric,dash') ?: null;
$download  = $input->get('download','alphanumeric') ?: null;

$formats          = json_decode(file_get_contents(APP_CONFIGDIR.'/database/changes/display.format.json'),true);
$dbDiffDir        = sprintf("%s/database/changes/diffs",APP_CONFIGDIR);
$dbDiffFileFormat = "takp.diff.%s.%s.json";
$diffValue        = '20240203-20240421';
$diffValue        = '20240717-20240828';

list($diffOldDate,$diffNewDate) = explode('-',$diffValue);

$diffFileName = sprintf($dbDiffFileFormat,$diffOldDate,$diffNewDate);

$diff = json_decode(file_get_contents(sprintf("%s/%s",$dbDiffDir,$diffFileName)),true);

include 'ui/header.php';

ksort($diff['modifiedTables']);

foreach ($diff['modifiedTables'] as $tableName => $stateList) {
    if (is_string($formats['table'][$tableName]['default'])) { continue; }

    $formatLabels = $formats['labels'][$tableName];

    $tableFormatKeys = array_map(function($value) use ($formatLabels) { return "@$value|".$formatLabels[$value].":warning@"; },$formats['table'][$tableName]['default'] ?: []);
    $tableFormatVals = array_map(function($value) { return '{{'.preg_replace('/^(\w+).*$/','$1',$value).'}}'; },$formats['table'][$tableName]['default'] ?: []);

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
            // If we have a translated entry, skip this raw entry and use that one instead
            if ($rowData['after']['_'.$changeKey]) { continue; }

            $changeKeyValue   = ltrim($changeKey,'_');
            $changeKeyLabel   = $formats['labels'][$tableName][$changeKeyValue];
            $changeKeyDisplay = ($changeKeyLabel) ? "$changeKeyLabel ($changeKeyValue)" : $changeKeyValue;
            $objectChanges[]  = sprintf("<small class='badge badge-secondary'>%s</small><small>(%s -> %s)</small>",$changeKeyDisplay,$rowData['before'][$changeKey],$newValue);
        }

        $rowData['OBJECTCHANGES'] = implode(' ',$objectChanges);

        $changes[] = displayFormattedData("<tr><td>".implode("</td><td>",array_values($formats['global']['changed'] ?: []))."</td></tr>\n",$rowData);
    }

    $changesHeader = preg_replace_callback('/@(?<key>\S+?)@/','badgeReplace',"<tr><th>".implode("</td><td>",array_keys($formats['global']['changed'] ?: []))."</th></tr>\n");
    $changesTable  = ($changes) ? sprintf("<table class='table table-sm table-striped' border=0>\n%s\n%s</table>",$changesHeader,implode('',$changes)) : '';

    $sectionTitle = sprintf("<span class='text-lime'>%s</span> (%d added, %d removed, %d changed)",$tableName,$stateList['added'],$stateList['removed'],$stateList['changed']);


    print $alte->displayCard($alte->displayRow(
        $addsRemovesTable.$changesTable,
        array('container' => 'col-xl-12 col-12')
    ),array('container' => 'col-xl-12 col-12', 'title' => $sectionTitle, 'card' => 'card-secondary collapsed-card', 'extra' => "data-card-widget='collapse'", 
            'tools' => "<button type='button' class='btn btn-tool' data-card-widget='collapse' data-expand-icon='fa-caret-down' data-collapse-icon='fa-caret-up'><i class='fa fa-caret-down'></i></button>"));
}

include 'ui/footer.php';

?>
<?php

function badgeReplace($matches)
{
    list($valueLabel,$badgeColor) = explode(':',$matches['key']);
    list($value,$label)           = explode('|',$valueLabel);

    if (!$badgeColor) { $badgeColor = 'secondary'; }

    return sprintf("<small class='badge badge-%s'>%s</small>",$badgeColor,$label ? "$label ($value)" : $value);
}

function displayFormattedData($format, $values) 
{
    if (!is_null($values) && is_array($values)) {
        $replace = array();
        foreach ($values as $key => $value) { 
            if (isset($values['_'.$key])) { continue; }

            $replace['{{'.ltrim($key,'_').'}}'] = ((is_array($value)) ? implode('; ',array_filter(array_unique($value))) : ((is_bool($value)) ? json_encode($value) : $value)); 
        }

      $format = str_replace(array_keys($replace),array_values($replace),$format);
    }

   return $format;
}

?>