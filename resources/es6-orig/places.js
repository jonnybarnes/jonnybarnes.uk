//places.js

import addMap from './mapbox-utils';
import getIcon from './edit-place-icon';

let div = document.querySelector('.map');
let map = addMap(div);
let isDragging;
let isCursorOverPoint;
let canvas = map.getCanvasContainer();

let selectElem = document.querySelector('select[name="icon"]');
selectElem.addEventListener('click', function () {
    let newIcon = getIcon();
    let source = map.getSource('points');
    if (source._data.features[0].properties.icon != newIcon) {
        source._data.features[0].properties.icon = newIcon;
        map.getSource('points').setData(source._data);
    }
});

function updateFormCoords(coords) {
    let latInput = document.querySelector('#latitude');
    let lonInput = document.querySelector('#longitude');
    latInput.value = coords.lat.toPrecision(6);
    lonInput.value = coords.lng.toPrecision(6);
}

function mouseDown() {
    if (!isCursorOverPoint) return;

    isDragging = true;

    // Set a cursor indicator
    canvas.style.cursor = 'grab';

    // Mouse events
    map.on('mousemove', onMove);
    map.once('mouseup', onUp);
}

function onMove(e) {
    if (!isDragging) return;
    let coords = e.lngLat;
    let source = map.getSource('points');

    // Set a UI indicator for dragging.
    canvas.style.cursor = 'grabbing';

    // Update the Point feature in `geojson` coordinates
    // and call setData to the source layer `point` on it.
    source._data.features[0].geometry.coordinates = [coords.lng, coords.lat];
    map.getSource('points').setData(source._data);
}

function onUp(e) {
    if (!isDragging) return;
    let coords = e.lngLat;

    // Print the coordinates of where the point had
    // finished being dragged to on the map.
    updateFormCoords(coords);
    canvas.style.cursor = '';
    isDragging = false;

    // Unbind mouse events
    map.off('mousemove', onMove);
}

// When the cursor enters a feature in the point layer, prepare for dragging.
map.on('mouseenter', 'points', function() {
    canvas.style.cursor = 'move';
    isCursorOverPoint = true;
    map.dragPan.disable();
});

map.on('mouseleave', 'points', function() {
    canvas.style.cursor = '';
    isCursorOverPoint = false;
    map.dragPan.enable();
});

map.on('mousedown', mouseDown);
