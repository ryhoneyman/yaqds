<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PQ Zone Map Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/zoneviewer.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-900 flex flex-col items-center justify-center h-screen p-4">

    <h1 id="pageTitle" class="text-3xl font-bold text-gray-800">Zone Map Viewer</h1>

    <!-- Top Controls Area -->
    <div class="my-4 w-full max-w-4xl flex items-end space-x-8">
        <!-- Map Selector Dropdown -->
        <div class="relative w-1/3">
            <label for="mapSearch" class="text-sm font-medium text-gray-600">Select Map</label>
            <input type="text" id="mapSearch" placeholder="Search maps..." class="w-full p-2 border border-gray-300 rounded-md mt-1">
            <div id="mapDropdown" class="absolute hidden w-full bg-white border border-gray-300 rounded-md mt-1 z-20 max-h-48 overflow-y-auto">
                <!-- Map items will be injected here by JS -->
            </div>
        </div>
        <!-- NPC Selector Dropdown -->
        <div class="relative w-1/3">
            <label for="npcSearch" class="text-sm font-medium text-gray-600">Find NPC</label>
            <input type="text" id="npcSearch" placeholder="Search NPCs..." class="w-full p-2 border border-gray-300 rounded-md mt-1">
            <div id="npcDropdown" class="absolute hidden w-full bg-white border border-gray-300 rounded-md mt-1 z-20 max-h-48 overflow-y-auto">
                <!-- NPC items will be injected here by JS -->
            </div>
        </div>
        <!-- Z-Axis Sliders -->
        <div class="flex-grow">
            <label class="text-lg font-semibold text-gray-700 text-center block">Z-Range: <span id="z-range-label" class="font-bold">0 to 0</span></label>
            <div class="flex items-center space-x-4 mt-2">
                <div class="flex-1">
                    <label for="zMinSlider" class="text-sm font-medium text-gray-600">Min Z</label>
                    <input type="range" id="zMinSlider" min="0" max="100" value="0" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                </div>
                <div class="flex-1">
                    <label for="zMaxSlider" class="text-sm font-medium text-gray-600">Max Z</label>
                    <input type="range" id="zMaxSlider" min="0" max="100" value="0" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                </div>
            </div>
        </div>
    </div>

    <div class="w-full max-w-7xl flex-grow">
        <div class="w-full h-full border-2 border-gray-300 rounded-lg shadow-lg overflow-hidden">
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
</body>
</html>
