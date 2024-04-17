<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'debugLevel'     => 0,
   'debugType'      => DEBUG_HTML,
   'errorReporting' => false,
   'sessionStart'   => true,
   'memoryLimit'    => null,
   'sendHeaders'    => true,
   'dbConfigDir'    => APP_CONFIGDIR,
   'fileDefine'     => APP_CONFIGDIR.'/defines.json',
   'database'       => true,
   'input'          => true,
   'html'           => true,
   'adminlte'       => true,
   'data'           => APP_CONFIGDIR.'/global.json',
));

include 'ui/header.php';

?>

<div class="row mb-4">
   <div class="col-12">
      <h1>Welcome to Yet Another Quarm Database Site!</h1>
      <h3>This site offers a comprehensive collection of information and resources for players of Project Quarm.</h3>
   </div>
</div>

<div class="row">

<?php

print infoBox('fa-map-marked-alt','bg-warning','/zone/viewer/','Zone Viewer','Load zone maps to view terrain and spawn data','new');
print infoBox('fa-gem','bg-primary','/loot/','Loot Viewer','Simulate loot drops from NPCs','new');
print infoBox('fa-database','bg-danger','https://www.pqdi.cc/','PQDI','Talador\'s Project Quarm Database Interface!','popular');

?>

</div>

<?php

function infoBox($icon, $bgColor, $link, $title, $description, $ribbon = null)
{
   $ribbonList = array(
      'new'     => '<div class="ribbon bg-danger">NEW</div>',
      'popular' => '<div class="ribbon bg-warning">POPULAR</div>',
   );

   return "   <div class='col-10 col-sm-6 col-md-4 col-lg-4 col-xl-4'>\n".
          "      <div class='info-box'>\n".
          "         <span class='info-box-icon $bgColor elevation-1'><a href='$link'><i class='fas $icon' aria-hidden='true'></i></a></span>\n".
          "         <div class='ribbon-wrapper'>\n".
          (($ribbonList[$ribbon]) ? "            ".$ribbonList[$ribbon] : '').
          "         </div>\n".
          "         <div class='info-box-content'>\n".
          "            <span class='info-box-text'> <a href='$link'><b>$title</b></a></span>\n".
          "            <span class='info-box-number' style='font-weight:normal;'>\n".
          "               $description\n".
          "            </span>\n".
          "         </div>\n".
          "      </div>\n".
          "   </div>\n";

}

include 'ui/footer.php';

?>
