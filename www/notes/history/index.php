<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'sendHeaders' => true,
));

$main->title('Patch Notes History');
$main->pageDescription('History and content for patch releases');

include 'ui/header.php';

$versionList = array(
   array(
      'version' => '1.5.0',
      'datetime' => '2024-08-26 13:30 UTC',
      'changes' => array(
         'system' => array(
            'Loaded 20240717 database (TAKP post-merge)',
            'Loaded 20240728 database (major post-merge fixes)',
            'Loaded 20240801 database (minor post-merge fixes)',
            'Loaded 20240817 database (minor post-merge fixes)',
            'Loaded 20240825 database',
         ),
         'frontend' => array(
            'Added expansion icons'
         ),
         'backend'  => array(
            'Refactored expansion in codebase, post-merge',
         ),
         'library'  => array(),
      ),
   ),
   array(
      'version' => '1.4.0',
      'datetime' => '2024-07-01 11:00 UTC',
      'changes' => array(
         'system' => array(
            'Loaded 20240701 database (Kunark release day)'
         ),
         'frontend' => array(),
         'backend'  => array(),
         'library'  => array(),
      ),
   ),
   array(
      'version' => '1.3.1',
      'datetime' => '2024-06-18 13:00 UTC',
      'changes' => array(
         'system' => array(
            'Loaded 20240618 database'
         ),
         'frontend' => array(),
         'backend'  => array(),
         'library'  => array(),
      ),
   ),
   array(
      'version' => '1.3.0',
      'datetime' => '2024-06-14 20:30 UTC',
      'changes' => array(
         'system' => array(),
         'frontend' => array(
            'Added Database Changes (used to perform enriched database differentials)'
         ),
         'backend'  => array(),
         'library'  => array(),
      ),
   ),
   array(
      'version' => '1.2.0',
      'datetime' => '2024-05-29 13:00 UTC',
      'changes' => array(
         'system' => array(
            'Loaded 20240529-0146 database (pre-Kunark drop)'
         ),
         'frontend' => array(),
         'backend'  => array(
            'Forced Kunark expansion release ahead of PQ Kunark open'
         ),
         'library'  => array(),
      ),
   ),
   array(
      'version' => '1.1.0',
      'datetime' => '2024-05-06 02:00 UTC',
      'changes' => array(
         'system' => array(
         ),
         'frontend' => array(
            'Map Viewer: Major performance improvements',
            'Map Viewer: Target search capability',
            'Map Viewer: Corrected display of coordinates',
            'Map Viewer: Added background, line/label adjustments',
            'Map Viewer: Roambox enabled',
            'Map Viewer: Corrected spawn grids for certain zones',
            'Map Viewer: Support for multiple group spawn at same location',
            'Map Viewer: Click added to pin/unpin pathing for group'
         ),
         'backend'  => array(),
         'library'  => array(),
      ),
   ),
   array(
      'version' => '1.0.2',
      'datetime' => '2024-05-01 11:30 UTC',
      'changes' => array(
         'system' => array(
            'Loaded 20240415-2251 database'
         ),
         'frontend' => array(
            'Added database version to footer'
         ),
         'backend'  => array(),
         'library'  => array(),
      ),
   ),
   array(
      'version' => '1.0.1',
      'datetime' => '2024-04-12 14:30 UTC',
      'changes' => array(
         'system' => array(),
         'frontend' => array(
            'Added Loot Viewer'
         ),
         'backend'  => array(),
         'library'  => array(),
      ),
   ),
   array(
      'version' => '1.0.0',
      'datetime' => '2024-02-09 19:00 UTC',
      'changes' => array(
         'system' => array(
            'Initial build',
         ),
         'frontend' => array(),
         'backend'  => array(),
         'library'  => array(),
      ),
   ), 
);

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
