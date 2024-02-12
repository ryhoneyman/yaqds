
<aside class="main-sidebar sidebar-dark-primary elevation-4">
   <!-- Brand Logo -->
   <a href="/index.php" class="brand-link">
      <img src="/images/logo.png" alt="Logo" class="brand-image img-circle" style="opacity: .8">
      <span class="brand-text font-weight-light">YAQDS</span>
   </a>

   <!-- Sidebar -->
   <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="mt-2" style='font-size:0.9em;'>
         <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

<?php

print insertHeader('ZONES').
      insertEntry('fa-map-marked-alt','/zone/viewer/','Zone Viewer').
      insertSpacer().
      insertEntry('fa-comment-lines','/notes/history/','Patch Notes').
      '';

?>

         </ul>
      </nav>
   </div>
</aside>

<?php

function insertHeader($headerName) {
   return "<li class='nav-header'><b>$headerName</b></li>\n";
}

function insertEntry($icon, $link, $title)
{
   $active = preg_match("~^$link~i",$_SERVER['REQUEST_URI']) ? ' active' : '';

   return "            <li class='nav-item'>\n".
          "               <a href='$link' class='nav-link{$active}'>\n".
          "                  <i class='nav-icon fa $icon'></i>\n".
          "                  <p> $title </p>\n".
          "               </a>\n".
          "            </li>\n";
}

function insertSpacer()
{
   return "<li class='nav-header mt-3'><b></b></li>";
}

?>
