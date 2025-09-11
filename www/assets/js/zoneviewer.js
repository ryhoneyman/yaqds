
// --- DOM ELEMENTS ---
const pageTitle = document.getElementById('pageTitle');
const canvas = document.getElementById('mapCanvas');
const ctx = canvas.getContext('2d');
const tooltip = document.getElementById('tooltip');
const statsTooltip = document.getElementById('statsTooltip');
const zMinSlider = document.getElementById('zMinSlider');
const zMaxSlider = document.getElementById('zMaxSlider');
const zRangeLabel = document.getElementById('z-range-label');
const mapSearch = document.getElementById('mapSearch');
const mapDropdown = document.getElementById('mapDropdown');
const npcSearch = document.getElementById('npcSearch');
const npcDropdown = document.getElementById('npcDropdown');
const npcInfoToggle = document.getElementById('npcInfoToggle');
const npcInfoSlider = document.getElementById('npcInfoSlider');
const npcSliderContent = document.getElementById('npcSliderContent');

// --- MAP DATA ---
let mapsData = [];

// --- DATA STRUCTURES ---
let lines = [];
let pointsOfInterest = [];
let spawnPoints = [];
let paths = new Map();
let activeSpawnPoint = null;
let highlightedSpawnPoints = [];

// --- VIEWPORT & ANIMATION STATE ---
let zoom = 0.8;
let pan = { x: 0, y: 0 };
let isPanning = false;
let panStart = { x: 0, y: 0 };
let lineDashOffset = 0;
let minZVisible = 0;
let maxZVisible = 0;
let pulseRadius = 0;
let pulseDirection = 1;
let dragOccurred = false;


/**
 * Generates formatted HTML for NPC spawn information
 */
function generateNpcSpawnHtml(npc) {
    if (!npc || !npc.spawns || npc.spawns.length === 0) {
        return '';
    }

    const groupParts = npc.info.split('-');
    const groupId    = groupParts[0].trim();
    const groupName  = groupParts.slice(1).join('-').trim();

    let statsHtml = `<div class="mb-2"><span class="text-gray-500">${groupName} (${groupId})</span></div><hr class="mb-1">`;

    npc.spawns.forEach(spawnText => {
        const parts = spawnText.split(' ');
        const chance = parts[0];
        const level = parts[parts.length - 1];
        const name = parts.slice(1, -1).join(' ');
        statsHtml += `<div class="mb-1"><strong>${chance}</strong> ${name} <span class="text-red-600">${level}</span></div>`;
    });

    // Add movement information
    statsHtml += '<hr class="my-2">';
    if (npc.pathId) {
        statsHtml += `<div class="text-xs text-blue-600"><strong>Path:</strong> Grid ${npc.pathId}</div>`;
    } else if (npc.roambox) {
        statsHtml += `<div class="text-xs text-green-600"><strong>Roambox:</strong> (${npc.roambox.x1}, ${npc.roambox.y1}) to (${npc.roambox.x2}, ${npc.roambox.y2})</div>`;
    } else {
        statsHtml += `<div class="text-xs text-gray-500"><strong>Static</strong></div>`;
    }
            
    return statsHtml;
}

function updateTooltips() {
    if (activeSpawnPoint) {
        const statsHtml = generateNpcSpawnHtml(activeSpawnPoint);
        
        if (statsHtml) {
            // Show the toggle button and populate the slider content
            npcInfoToggle.classList.remove('hidden');
            npcSliderContent.innerHTML = statsHtml;
            
            // Auto-open the slider when an NPC is selected
            npcInfoSlider.classList.remove('translate-y-full');
            
            // Hide the floating tooltip
            statsTooltip.style.display = 'none';
        }
    } else {
        // Hide the toggle button and slider when no NPC is selected
        npcInfoToggle.classList.add('hidden');
        npcInfoSlider.classList.add('translate-y-full');
        statsTooltip.style.display = 'none';
    }
}

/**
 * Loads map data from files in the maps directory
 */
async function loadMapsFromFiles() {
    try {
        // Load only map metadata from API
        const response = await fetch('/r/maps');
        const mapMetadata = await response.json();
        
        mapsData = [];
        
        // Store only metadata, no map data yet
        Object.values(mapMetadata).forEach(mapMeta => {
            if (!/_(instanced|alt|tryout)/.test(mapMeta.short_name)) {
                mapsData.push({
                    name: mapMeta.short_name,
                    id: mapMeta.zoneidnumber,
                    label: mapMeta.long_name,
                    data: null // No data loaded yet
                });
            }
        });
        
        if (mapsData.length === 0) {
            console.warn('No map metadata loaded');
        }
        
    } catch (error) {
        console.error('Failed to load map metadata from API:', error);
    }
}

/**
 * Loads specific map data from API when user selects a map
 */
async function loadMapData(mapName) {
    try {
        const response = await fetch(`/r/map/${mapName}`);
        const jsonData = await response.json();
        
        // Convert the JSON "map" array to string format expected by parseData
        if (jsonData.map && Array.isArray(jsonData.map)) {
            const mapDataString = jsonData.map.join('\n');
            return mapDataString;
        } else {
            console.error('Invalid map data format - missing "map" array');
            return null;
        }
    } catch (error) {
        console.error(`Failed to load map data for ID ${mapId}:`, error);
        return null;
    }
}

/**
 * Populates the map selection dropdown.
 */
function populateMapDropdown() {
    mapDropdown.innerHTML = '';
    mapsData.forEach(map => {
        const a = document.createElement('a');
        a.href = '#';
        a.textContent = map.label;
        a.dataset.mapName = map.label;
        a.className = 'block px-4 py-2 text-sm text-gray-700';
        mapDropdown.appendChild(a);
    });
}

/**
 * Populates the NPC selection dropdown.
 */
function populateNpcDropdown() {
    npcDropdown.innerHTML = '';
    const uniqueNpcs = new Set();

    spawnPoints.forEach(spawn => {
        spawn.spawns.forEach(spawnText => {
            const parts = spawnText.split(' ');
            const name = parts.slice(1, -1).join(' ');
            uniqueNpcs.add(name);
        });
    });

    uniqueNpcs.forEach(name => {
        const a = document.createElement('a');
        a.href = '#';
        a.textContent = name;
        a.dataset.npcName = name;
        a.className = 'block px-4 py-2 text-sm text-gray-700';
        npcDropdown.appendChild(a);
    });
}

/**
 * Calculates the optimal zoom and pan to fit all map content on screen
 */
function fitMapToScreen() {
    if (lines.length === 0 && pointsOfInterest.length === 0 && spawnPoints.length === 0) return;
    
    let minX = Infinity, maxX = -Infinity;
    let minY = Infinity, maxY = -Infinity;
    
    // Calculate bounds from all lines
    lines.forEach(line => {
        minX = Math.min(minX, line.start.x, line.end.x);
        maxX = Math.max(maxX, line.start.x, line.end.x);
        minY = Math.min(minY, line.start.y, line.end.y);
        maxY = Math.max(maxY, line.start.y, line.end.y);
    });
    
    // Calculate bounds from all POIs and spawn points
    [...pointsOfInterest, ...spawnPoints].forEach(poi => {
        minX = Math.min(minX, poi.x);
        maxX = Math.max(maxX, poi.x);
        minY = Math.min(minY, poi.y);
        maxY = Math.max(maxY, poi.y);
    });
    
    // Add some padding
    const padding = 50;
    const mapWidth = maxX - minX + padding * 2;
    const mapHeight = maxY - minY + padding * 2;
    
    // Calculate zoom to fit the map in the canvas
    const zoomX = canvas.width / mapWidth;
    const zoomY = canvas.height / mapHeight;
    zoom = Math.min(zoomX, zoomY, 2); // Cap at 2x zoom
    
    // Center the map
    const centerX = (minX + maxX) / 2;
    const centerY = (minY + maxY) / 2;
    pan.x = -centerX * zoom;
    pan.y = -centerY * zoom;
}

/**
 * Parses data, separating generic lines, POIs, and NPCs with their paths and stats.
 */
function parseData(data) {
    lines = [];
    pointsOfInterest = [];
    spawnPoints = [];
    paths.clear();
    activeSpawnPoint = null;
    highlightedSpawnPoints = [];
    let minZ = Infinity;
    let maxZ = -Infinity;

    const dataLines = data.split('\n').filter(line => line.trim() !== '' && !line.trim().startsWith('//'));

    // Define line type handlers
    const lineHandlers = {
        'L ': 'L',
        'N ': 'N', 
        'P ': 'P'
    };

    dataLines.forEach((line, index) => {
        const trimmedLine = line.trim();
        
        // Find the line type using the handlers map
        const lineType = Object.keys(lineHandlers).find(prefix => trimmedLine.startsWith(prefix));
        
        if (!lineType) {
            return; // Skip lines without a valid prefix
        }
        
        const processedLine = trimmedLine.substring(lineType.length);
        const parts = processedLine.split(',').map(p => p.trim());
        const typeCode = lineHandlers[lineType];
        
        if (typeCode === 'P') {
            if (parts.length < 7) return;
            const pathId = parts[0];
            const numericParts = parts.slice(1).map(Number);
            if (numericParts.some(isNaN)) return;
            const [x1, y1, z1, x2, y2, z2] = numericParts;
            if (!paths.has(pathId)) {
                paths.set(pathId, []);
            }
            paths.get(pathId).push({ start: { x: x1, y: y1, z: z1 }, end: { x: x2, y: y2, z: z2 } });
            return;
        } 
        else if (typeCode === 'N') {
            // NPC format: N x,y,z,r,g,b,description,spawnList,pathId/roambox
            if (parts.length < 6) return;

            const numericParts = parts.slice(0, 6).map(Number);
            if (numericParts.some(isNaN)) return;

            const [x, y, z, r, g, b] = numericParts;

            minZ = Math.min(minZ, z);
            maxZ = Math.max(maxZ, z);

            const color = `rgb(${r},${g},${b})`;
            const description = parts.length > 6 ? parts[6] : '';
            const spawnListString = parts.length > 7 ? parts[7] : '';
            const pathData = parts.length > 8 ? parts[8] : '0';

            // Parse pathData - could be pathId, roambox coordinates, or 0
            let pathId = null;
            let roambox = null;

            if (pathData && pathData !== '0') {
                // Check if it contains commas (roambox format: x1^y1^x2^y2)
                if (pathData.includes('^')) {
                    const roamCoords = pathData.split('^').map(coord => parseFloat(coord.trim()));
                    if (roamCoords.length === 4 && !roamCoords.some(isNaN)) {
                        roambox = {
                            x1: roamCoords[0],
                            y1: roamCoords[1], 
                            x2: roamCoords[2],
                            y2: roamCoords[3]
                        };
                    }
                } else {
                    // Numeric path ID
                    const numericPathId = parseInt(pathData, 10);
                    if (!isNaN(numericPathId) && numericPathId > 0) {
                        pathId = numericPathId.toString();
                    }
                }
            }

            const spawns = spawnListString.split('|').map(s => s.trim());
            spawnPoints.push({
                id: `spawn-${index}`,
                x: x, y: y, z: z,
                color: color,
                info: description.replace(/\\n/g, '\n'),
                spawns: spawns,
                pathId: pathId,
                roambox: roambox,
                pathVisible: false,
                roamboxVisible: false
            });
            return;
        }
        else if (typeCode == 'L') {
            if (parts.length < 9) return;

            const numericParts = parts.slice(0, 9).map(Number);
            if (numericParts.some(isNaN)) return;

            const [x1, y1, z1, x2, y2, z2, r, g, b] = numericParts;
            
            minZ = Math.min(minZ, z1, z2);
            maxZ = Math.max(maxZ, z1, z2);

            const color = `rgb(${r},${g},${b})`;
            const description = parts.length > 9 ? parts[9] : '';
            const spawnListString = parts.length > 10 ? parts[10] : '';
            const pathId = parts.length > 11 ? parts[11] : null;

            lines.push({ start: { x: x1, y: y1, z: z1 }, end: { x: x2, y: y2, z: z2 }, color: color });
        }
    });
    
    populateNpcDropdown();

    zMinSlider.min = minZ;
    zMinSlider.max = maxZ;
    zMinSlider.value = minZ;
    minZVisible = minZ;

    zMaxSlider.min = minZ;
    zMaxSlider.max = maxZ;
    zMaxSlider.value = maxZ;
    maxZVisible = maxZ;
    
    zRangeLabel.textContent = `${minZVisible} to ${maxZVisible}`;

    // Fit the map to screen after parsing
    fitMapToScreen();
}

function project(point3D) {
    const x = (point3D.x * zoom) + pan.x;
    const y = (point3D.y * zoom) + pan.y;
    return { x: x + canvas.width / 2, y: y + canvas.height / 2 };
}

function drawArrowhead(ctx, from, to, headLength) {
    const angle = Math.atan2(to.y - from.y, to.x - from.x);
    const x1 = to.x - headLength * Math.cos(angle - Math.PI / 6);
    const y1 = to.y - headLength * Math.sin(angle - Math.PI / 6);
    const x2 = to.x - headLength * Math.cos(angle + Math.PI / 6);
    const y2 = to.y - headLength * Math.sin(angle + Math.PI / 6);
    ctx.beginPath();
    ctx.moveTo(to.x, to.y);
    ctx.lineTo(x1, y1);
    ctx.moveTo(to.x, to.y);
    ctx.lineTo(x2, y2);
    ctx.stroke();
}

function draw() {
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    ctx.setLineDash([]);
    lines.forEach(line => {
        const lineMinZ = Math.min(line.start.z, line.end.z);
        const lineMaxZ = Math.max(line.start.z, line.end.z);
        if (lineMaxZ < minZVisible || lineMinZ > maxZVisible) return;

        const p1 = project(line.start);
        const p2 = project(line.end);
        ctx.beginPath();
        ctx.moveTo(p1.x, p1.y);
        ctx.lineTo(p2.x, p2.y);
        ctx.strokeStyle = line.color;
        ctx.lineWidth = 2;
        ctx.stroke();
    });

    // Draw NPC paths and roamboxes
    spawnPoints.forEach(spawn => {
        // Draw paths
        if (spawn.pathVisible && spawn.pathId && paths.has(spawn.pathId)) {
            ctx.save();
            ctx.strokeStyle = 'rgb(59, 130, 246)';
            ctx.lineWidth = 3;
            ctx.setLineDash([8, 8]);
            ctx.lineDashOffset = lineDashOffset;
            const path = paths.get(spawn.pathId);
            path.forEach(pathLine => {
                const lineMinZ = Math.min(pathLine.start.z, pathLine.end.z);
                const lineMaxZ = Math.max(pathLine.start.z, pathLine.end.z);
                if (lineMaxZ < minZVisible || lineMinZ > maxZVisible) return;

                const p1 = project(pathLine.start);
                const p2 = project(pathLine.end);
                
                ctx.beginPath();
                ctx.moveTo(p1.x, p1.y);
                ctx.lineTo(p2.x, p2.y);
                ctx.stroke();
                
                drawArrowhead(ctx, p1, p2, 10);
            });
            ctx.restore();
        }
        
        // Draw roamboxes
        if (spawn.roamboxVisible && spawn.roambox) {
            ctx.save();
            ctx.strokeStyle = 'rgb(34, 197, 94)'; // Green color for roamboxes
            ctx.fillStyle = 'rgba(34, 197, 94, 0.1)'; // Semi-transparent fill
            ctx.lineWidth = 2;
            ctx.setLineDash([4, 4]);
            ctx.lineDashOffset = lineDashOffset;
            
            const topLeft = project({ x: spawn.roambox.x1, y: spawn.roambox.y1, z: spawn.z });
            const bottomRight = project({ x: spawn.roambox.x2, y: spawn.roambox.y2, z: spawn.z });
            
            const width = bottomRight.x - topLeft.x;
            const height = bottomRight.y - topLeft.y;
            
            // Fill the roambox area
            ctx.fillRect(topLeft.x, topLeft.y, width, height);
            
            // Draw the roambox border
            ctx.beginPath();
            ctx.rect(topLeft.x, topLeft.y, width, height);
            ctx.stroke();
            ctx.restore();
        }
    });

    // Draw POIs (always normal brightness)
    pointsOfInterest.forEach(poi => {
        if (poi.z >= minZVisible && poi.z <= maxZVisible) {
            const p = project(poi);
            ctx.beginPath();
            ctx.arc(p.x, p.y, 7, 0, Math.PI * 2);
            ctx.fillStyle = poi.color;
            ctx.fill();
            ctx.strokeStyle = 'black';
            ctx.lineWidth = 2;
            ctx.stroke();
        }
    });

    // Draw spawn points with dimming effect
    spawnPoints.forEach(spawn => {
        if (spawn.z >= minZVisible && spawn.z <= maxZVisible) {
            const p = project(spawn);
            
            // Determine if this NPC should be dimmed
            const isDimmed = activeSpawnPoint && activeSpawnPoint !== spawn;
            
            ctx.save();
            
            if (isDimmed) {
                // Apply dimming effect
                ctx.globalAlpha = 0.1;
            }
            
            ctx.beginPath();
            ctx.arc(p.x, p.y, 7, 0, Math.PI * 2);
            ctx.fillStyle = spawn.color;
            ctx.fill();
            ctx.strokeStyle = 'black';
            ctx.lineWidth = 2;
            ctx.stroke();
            
            ctx.restore();
        }
    });
    
    // Draw highlight pulses (always full brightness)
    highlightedSpawnPoints.forEach(spawn => {
        const p = project(spawn);
        ctx.save();
        ctx.strokeStyle = `rgba(0, 100, 255, ${1 - pulseRadius / 10})`;
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.arc(p.x, p.y, 10 + pulseRadius, 0, Math.PI * 2);
        ctx.stroke();
        ctx.restore();
    });
}

function resizeCanvas() {
    const container = canvas.parentElement;
    canvas.width = container.clientWidth;
    canvas.height = container.clientHeight;
}

function animate() {
    lineDashOffset -= 0.5;
    if (lineDashOffset < -16) lineDashOffset = 0;
    
    pulseRadius += 0.2 * pulseDirection;
    if (pulseRadius > 5 || pulseRadius < 0) {
        pulseDirection *= -1;
    }

    draw();
    updateTooltips();
    requestAnimationFrame(animate);
}

// --- EVENT LISTENERS ---

zMinSlider.addEventListener('input', (event) => {
    minZVisible = parseInt(event.target.value, 10);
    if (minZVisible > maxZVisible) {
        maxZVisible = minZVisible;
        zMaxSlider.value = maxZVisible;
    }
    zRangeLabel.textContent = `${minZVisible} to ${maxZVisible}`;
});

zMaxSlider.addEventListener('input', (event) => {
    maxZVisible = parseInt(event.target.value, 10);
    if (maxZVisible < minZVisible) {
        minZVisible = maxZVisible;
        zMinSlider.value = minZVisible;
    }
    zRangeLabel.textContent = `${minZVisible} to ${maxZVisible}`;
});

canvas.addEventListener('wheel', (event) => {
    event.preventDefault();
    const zoomIntensity = 0.1;
    const scroll = event.deltaY < 0 ? 1 : -1;
    const newZoom = Math.max(0.1, Math.min(5, zoom * (1 + scroll * zoomIntensity)));
    
    // Get mouse position relative to canvas
    const rect = canvas.getBoundingClientRect();
    const mouseX = event.clientX - rect.left;
    const mouseY = event.clientY - rect.top;
    
    // Convert mouse position to world coordinates before zoom
    const worldX = (mouseX - canvas.width / 2 - pan.x) / zoom;
    const worldY = (mouseY - canvas.height / 2 - pan.y) / zoom;
    
    // Update zoom
    zoom = newZoom;
    
    // Adjust pan to keep the mouse position fixed
    pan.x = mouseX - canvas.width / 2 - worldX * zoom;
    pan.y = mouseY - canvas.height / 2 - worldY * zoom;
});

canvas.addEventListener('mousedown', (event) => {
    isPanning = true;
    dragOccurred = false;
    panStart.x = event.clientX - pan.x;
    panStart.y = event.clientY - pan.y;
    canvas.style.cursor = 'grabbing';
});

canvas.addEventListener('mouseup', () => {
    isPanning = false;
    canvas.style.cursor = 'grab';
});

canvas.addEventListener('mouseleave', () => {
    isPanning = false;
    canvas.style.cursor = 'grab';
    tooltip.style.display = 'none';
});

canvas.addEventListener('click', (event) => {
    if (dragOccurred) return;
    
    highlightedSpawnPoints = [];
    const rect = canvas.getBoundingClientRect();
    const mousePos = { x: event.clientX - rect.left, y: event.clientY - rect.top };

    let clickedOnObject = false;
    for (const spawn of spawnPoints) {
        const projectedNpc = project(spawn);
        const distance = Math.hypot(projectedNpc.x - mousePos.x, projectedNpc.y - mousePos.y);

        if (distance < 10) {
            clickedOnObject = true;
            highlightedSpawnPoints = [spawn];
            if (activeSpawnPoint === spawn) {
                // Toggle path or roambox visibility
                if (spawn.pathId) {
                    spawn.pathVisible = !spawn.pathVisible;
                } else if (spawn.roambox) {
                    spawn.roamboxVisible = !spawn.roamboxVisible;
                }
            } else {
                // Clear previous spawn point
                if (activeSpawnPoint) {
                    activeSpawnPoint.pathVisible = false;
                    activeSpawnPoint.roamboxVisible = false;
                }
                
                // Set new active spawn point and show its movement area
                activeSpawnPoint = spawn;
                if (spawn.pathId) {
                    spawn.pathVisible = true;
                } else if (spawn.roambox) {
                    spawn.roamboxVisible = true;
                }
            }
            break;
        }
    }
    if (!clickedOnObject) {
        highlightedSpawnPoints = [];
        // Clear the active spawn point and hide all paths/roamboxes when clicking away
        if (activeSpawnPoint) {
            activeSpawnPoint.pathVisible = false;
            activeSpawnPoint.roamboxVisible = false;
            activeSpawnPoint = null;
        }
    }
});

canvas.addEventListener('mousemove', (event) => {
    if (isPanning) {
        dragOccurred = true;
        pan.x = event.clientX - panStart.x;
        pan.y = event.clientY - panStart.y;
        return;
    }

    const rect = canvas.getBoundingClientRect();
    const mousePos = { x: event.clientX - rect.left, y: event.clientY - rect.top };

    let foundPoi = null;
    let foundNpc = null;
    const allInteractivePoints = [...pointsOfInterest, ...spawnPoints];

    for (const poi of allInteractivePoints) {
        if (poi.z >= minZVisible && poi.z <= maxZVisible) {
            const projectedPoi = project(poi);
            const distance = Math.hypot(projectedPoi.x - mousePos.x, projectedPoi.y - mousePos.y);
            if (distance < 10) {
                foundPoi = poi;
                // Check if this is a spawn point (NPC)
                if (spawnPoints.includes(poi)) {
                    foundNpc = poi;
                }
                break;
            }
        }
    }

    if (foundPoi) {
        if (foundNpc) {
            // Show NPC spawn information tooltip using shared function
            const statsHtml = generateNpcSpawnHtml(foundNpc);
            tooltip.innerHTML = statsHtml || foundPoi.info;
        } else {
            // Show regular POI description
            tooltip.innerText = foundPoi.info;
        }
        
        tooltip.style.display = 'block';
        tooltip.style.left = `${event.clientX + 15}px`;
        tooltip.style.top = `${event.clientY + 15}px`;
        canvas.style.cursor = 'pointer';
    } else {
        tooltip.style.display = 'none';
        canvas.style.cursor = 'grab';
    }
});

window.addEventListener('resize', resizeCanvas);

mapSearch.addEventListener('focus', () => {
    mapDropdown.classList.remove('hidden');
});

npcSearch.addEventListener('focus', () => {
    npcDropdown.classList.remove('hidden');
});

document.addEventListener('click', (event) => {
    if (!mapSearch.contains(event.target) && !mapDropdown.contains(event.target)) {
        mapDropdown.classList.add('hidden');
    }
    if (!npcSearch.contains(event.target) && !npcDropdown.contains(event.target)) {
        npcDropdown.classList.add('hidden');
    }
    
    // Close NPC info slider when clicking outside
    if (!npcInfoSlider.contains(event.target) && !npcInfoToggle.contains(event.target)) {
        npcInfoSlider.classList.add('translate-y-full');
    }
});

mapSearch.addEventListener('input', (event) => {
    const filter = event.target.value.toLowerCase();
    const options = mapDropdown.getElementsByTagName('a');

    mapDropdown.classList.remove('hidden');

    for (let i = 0; i < options.length; i++) {
        const txtValue = options[i].textContent || options[i].innerText;
        if (txtValue.toLowerCase().indexOf(filter) > -1) {
            options[i].style.display = "";
        } else {
            options[i].style.display = "none";
        }
    }
});

npcSearch.addEventListener('input', (event) => {
    const filter = event.target.value.toLowerCase();
    const options = npcDropdown.getElementsByTagName('a');

    npcDropdown.classList.remove('hidden');
    
    for (let i = 0; i < options.length; i++) {
        const txtValue = options[i].textContent || options[i].innerText;
        if (txtValue.toLowerCase().indexOf(filter) > -1) {
            options[i].style.display = "";
        } else {
            options[i].style.display = "none";
        }
    }
});

// Update the map dropdown click handler
mapDropdown.addEventListener('click', async (event) => {
    if (event.target.tagName === 'A') {
        const mapName = event.target.dataset.mapName;
        const selectedMap = mapsData.find(m => m.label === mapName);
        
        if (selectedMap) {
            // Show loading state
            mapSearch.value = `Loading ${mapName}...`;
            mapSearch.disabled = true;
            
            try {
                // Load map data dynamically from API
                const mapData = await loadMapData(selectedMap.name);
                
                if (mapData) {
                    // Store the loaded data in the map object
                    selectedMap.data = mapData;
                    
                    // Parse and display the map
                    parseData(selectedMap.data);
                    mapSearch.value = mapName;
                    mapDropdown.classList.add('hidden');
                    
                    // Update the page title with the zone name
                    pageTitle.textContent = mapName;
                } else {
                    // Handle loading error
                    mapSearch.value = `Error loading ${mapName}`;
                    setTimeout(() => {
                        mapSearch.value = '';
                    }, 2000);
                }
            } catch (error) {
                console.error('Error loading map:', error);
                mapSearch.value = `Failed to load ${mapName}`;
                setTimeout(() => {
                    mapSearch.value = '';
                }, 2000);
            } finally {
                mapSearch.disabled = false;
            }
        }
    }
});

npcDropdown.addEventListener('click', (event) => {
    if (event.target.tagName === 'A') {
        const npcName = event.target.dataset.npcName;

        // Find spawn points that contain this exact NPC name
        highlightedSpawnPoints = spawnPoints.filter(spawn => 
            spawn.spawns.some(spawnText => {
                const parts = spawnText.split(' ');
                const name = parts.slice(1, -1).join(' ');
                return name === npcName; // Exact match instead of partial
            })
        );

        if (highlightedSpawnPoints.length > 0) {
            npcSearch.value = npcName;
        }
        npcDropdown.classList.add('hidden');
    }
});

// Toggle the NPC info slider
npcInfoToggle.addEventListener('click', () => {
    const isVisible = !npcInfoSlider.classList.contains('translate-y-full');
    if (isVisible) {
        // Hide the slider
        npcInfoSlider.classList.add('translate-y-full');
    } else {
        // Show the slider
        npcInfoSlider.classList.remove('translate-y-full');
    }
});

function getQueryParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// --- INITIALIZATION ---
async function init() {
    await loadMapsFromFiles();
    populateMapDropdown();

    // Check for mapName query parameter
    const initialMapName = getQueryParameter('mapName');

    if (initialMapName && mapsData.length > 0) {
        // Find the map by name and trigger the existing click handler
        const selectedMap = mapsData.find(m => m.label === initialMapName);

        if (selectedMap) {
            // Find the corresponding dropdown item and trigger a click event
            const dropdownItems = mapDropdown.getElementsByTagName('a');
            for (let item of dropdownItems) {
                if (item.dataset.mapName === selectedMap.label) {
                    // Create and dispatch a click event
                    const clickEvent = new Event('click', {
                        bubbles: true,
                        cancelable: true
                    });
                    item.dispatchEvent(clickEvent);
                    break;
                }
            }
        } else {
            console.warn(`Map with name '${initialMapName}' not found`);
            mapSearch.placeholder = `${mapsData.length} maps available - select one`;
        }
    } else if (mapsData.length > 0) {
        // Just show available maps, let user select one
        mapSearch.placeholder = `${mapsData.length} maps available - select one`;
    }

    resizeCanvas();
    animate();
}
