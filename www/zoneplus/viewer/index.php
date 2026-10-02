<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'debugLevel'     => 0,
   'errorReporting' => false,
   'sessionStart'   => true,
   'memoryLimit'    => null,
   'sendHeaders'    => true,
   'dbConfigDir'    => APP_CONFIGDIR,
   'fileDefine'     => APP_CONFIGDIR.'/defines.json',
   'database'       => true,
   'input'          => false,
   'html'           => false,
   'adminlte'       => true,
   'data'           => APP_CONFIGDIR.'/global.json',
));

$main->title('Zone Viewer Plus');
$main->pageDescription('Enhanced map and spawn data viewer');

include 'ui/header.php';

?>
<!-- Tailwind (preflight disabled to avoid conflicting with AdminLTE) -->
<script>tailwind = { config: { corePlugins: { preflight: false } } }</script>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
<link href="/assets/css/zoneviewer.css" rel="stylesheet">

<style>
/* Hide the AdminLTE content header (title + HR) to reclaim vertical space */
.content-header { display: none !important; }

.zoneviewer-wrapper {
    font-family: 'Inter', sans-serif;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 145px);
    overflow: hidden;
}

/* Single compact toolbar row */
.zv-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 0;
    flex-shrink: 0;
}
.zv-toolbar .zv-zone-name {
    font-size: 1.25rem;
    font-weight: 700;
    white-space: nowrap;
    color: #ffc107;
    min-width: 140px;
}
.zv-toolbar .zv-field {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.zv-toolbar label {
    font-size: 0.7rem;
    font-weight: 500;
    color: #d1d5db;
    line-height: 1;
}
.zv-toolbar .zv-input-wrap {
    position: relative;
    display: inline-block;
}
.zv-toolbar input[type="text"] {
    padding: 4px 24px 4px 8px;
    font-size: 0.8rem;
    border: 1px solid #6b7280;
    border-radius: 4px;
    width: 255px;
    background: #1f2937;
    color: #f3f4f6;
}
.zv-toolbar input[type="text"]::placeholder { color: #9ca3af; }
.zv-clear-btn {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
    padding: 0;
}
.zv-clear-btn:hover { color: #f3f4f6; }
.zv-toolbar .zv-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 20;
    min-width: 100%;
    width: max-content;
    background: #1f2937;
    border: 1px solid #6b7280;
    border-radius: 4px;
    margin-top: 2px;
    max-height: 200px;
    overflow-y: auto;
}
.zv-toolbar .zv-dropdown a { color: #d1d5db !important; }
.zv-toolbar .zv-dropdown a:hover { background: #374151 !important; }
.zv-toolbar .zv-sliders {
    display: flex;
    align-items: center;
    gap: 10px;
}
.zv-toolbar .zv-slider-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.zv-toolbar input[type="range"] {
    width: 110px;
    height: 4px;
    cursor: pointer;
    accent-color: #3b82f6;
}
.zv-toolbar label span {
    color: #ffc107;
    font-weight: 700;
}

.zoneviewer-wrapper .canvas-container {
    flex: 1;
    overflow: hidden;
    border: 2px solid #4b5563;
    border-radius: 6px;
}
</style>

<div class="zoneviewer-wrapper p-2">

    <!-- Compact single-row toolbar -->
    <div class="zv-toolbar w-full">

        <!-- Zone name (updated by JS) -->
        <div id="pageTitle" class="zv-zone-name">Zone Map Viewer</div>

        <!-- Map Selector -->
        <div class="zv-field" style="position:relative;">
            <label for="mapSearch">Select Map</label>
            <div class="zv-input-wrap">
                <input type="text" id="mapSearch" placeholder="Search maps...">
                <button id="mapClear" class="zv-clear-btn hidden" title="Clear zone">&#x2715;</button>
            </div>
            <div id="mapDropdown" class="zv-dropdown hidden">
                <!-- injected by JS -->
            </div>
        </div>

        <!-- NPC Selector -->
        <div class="zv-field" style="position:relative;">
            <label for="npcSearch">Find NPC</label>
            <div class="zv-input-wrap">
                <input type="text" id="npcSearch" placeholder="Search NPCs...">
                <button id="npcClear" class="zv-clear-btn hidden" title="Clear NPC">&#x2715;</button>
            </div>
            <div id="npcDropdown" class="zv-dropdown hidden">
                <!-- injected by JS -->
            </div>
        </div>

        <!-- Z-Range sliders -->
        <div class="zv-sliders">
            <div class="zv-slider-group">
                <label for="zMinSlider">Min Z (<span id="z-min-label">0</span>)</label>
                <input type="range" id="zMinSlider" min="0" max="100" value="0">
            </div>
            <div class="zv-slider-group">
                <label for="zMaxSlider">Max Z (<span id="z-max-label">0</span>)</label>
                <input type="range" id="zMaxSlider" min="0" max="100" value="0">
            </div>
        </div>

    </div>

    <div class="canvas-container">
        <canvas id="mapCanvas"></canvas>
    </div>

</div>

<div id="tooltip" class="tooltip"></div>
<div id="statsTooltip" class="tooltip"></div>
<div id="coordinateDisplay" class="fixed top-4 left-4 bg-black bg-opacity-75 text-white px-3 py-2 rounded text-sm font-mono hidden">
    X: <span id="coordX">0</span>, Y: <span id="coordY">0</span>
</div>

<!-- NPC Info Toggle and Slider -->
<div id="npcInfoToggle" class="fixed bottom-4 right-4 bg-blue-600 text-white p-3 rounded-full shadow-lg cursor-pointer hidden">
    <span>📍</span>
</div>
<div id="npcInfoSlider" class="fixed bottom-0 right-0 w-80 bg-white border-l border-t border-gray-300 rounded-tl-lg shadow-lg transform translate-y-full transition-transform duration-300">
    <div class="p-4">
        <h3 class="font-semibold text-gray-800 mb-2">NPC Information</h3>
        <div id="npcSliderContent" class="text-sm text-gray-700"></div>
    </div>
</div>

<script src="/assets/js/zoneviewer.js"></script>

<script>
    init();
</script>

<?php include 'ui/footer.php'; ?>
