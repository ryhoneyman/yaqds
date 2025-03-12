<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'sendHeaders' => true,
));

$main->title('Patch Notes History');
$main->pageDescription('History and content for patch releases');

include 'ui/header.php';

$versionList  = json_decode(file_get_contents(APP_CONFIGDIR.'/patch-notes.json'),true) ?: [];
$notesDisplay = '';

foreach ($versionList as $versionPatch) {
   $notesDisplay .= displayPatchNote($versionPatch);
}

print $notesDisplay;

include 'ui/footer.php';

?>
<?php

function displayPatchNote($patchInfo)
{
   $changeAttrib = array(
      'system'   => array('label' => 'System', 'color' => 'text-primary'),
      'frontend' => array('label' => 'Frontend', 'color' => 'text-green'),
      'backend'  => array('label' => 'Backend', 'color' => 'text-red'),
      'library'  => array('label' => 'Library', 'color' => 'text-pink'),
   );

   $patchVersion  = $patchInfo['version'];
   $patchDatetime = $patchInfo['datetime'];

   $return = "<div class='row'>\n".
             "  <div class='col-12 col-xl-9 col-lg-10 col-md-12 col-sm-12'>\n".
             "    <div class='card card-outline card-success'>\n".
             "      <div class='card-header'>\n".
             "        <b class='text-xl'>v$patchVersion</b><div class='card-tools text-yellow'>$patchDatetime</div>\n".
             "      </div>\n".
             "      <div class='card-body'>\n".
             "        <ul>\n";

   foreach ($patchInfo['changes'] as $changeType => $changeList) {
      foreach ($changeList as $changeText) {
         $changeLabel = $changeAttrib[$changeType]['label'];
         $changeColor = $changeAttrib[$changeType]['color'];

         $return .= "             <li><span class='$changeColor'>$changeLabel:</span> $changeText</li>\n"; 
      }
   }

   $return .= "        </ul>\n".
              "      </div>\n".
              "    </div>\n".
              "  </div>\n".
              "</div>\n";

   return $return;
}

?>
