/* global mapboxgl */
//This code runs on page load and looks for <div class="map">, then adds map
var mapDivs = document.querySelectorAll('.map');
mapboxgl.accessToken = 'pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiY2l2cDhjYW04MDAwcjJ0cG1uZnhqcm82ayJ9.qA2zeVA-nsoMh9IFrd5KQw';
for (var i = 0; i < mapDivs.length; i++) {
    var mapDiv = mapDivs[i];
    var latitude = mapDiv.dataset.latitude;
    var longitude  = mapDiv.dataset.longitude;
    var el = document.createElement('div');
    el.classList.add('marker');
    var mapMenu = document.createElement('div');
    mapMenu.classList.add('map-menu');
    var streetsInput = document.createElement('input');
    streetsInput.setAttribute('id', 'streets');
    streetsInput.setAttribute('type', 'radio');
    streetsInput.setAttribute('name', 'toggle');
    streetsInput.setAttribute('value', 'streets');
    streetsInput.setAttribute('checked', 'checked');
    streetsInput.addEventListener('click', function () {
        map.setStyle('mapbox://styles/mapbox/streets-v9');
    });
    var streetsLabel = document.createElement('label');
    streetsLabel.setAttribute('for', 'streets');
    streetsLabel.appendChild(document.createTextNode('Streets'));
    var satelliteInput = document.createElement('input');
    satelliteInput.setAttribute('id', 'satellite');
    satelliteInput.setAttribute('type', 'radio');
    satelliteInput.setAttribute('name', 'toggle');
    satelliteInput.setAttribute('value', 'streets');
    satelliteInput.addEventListener('click', function () {
        map.setStyle('mapbox://styles/mapbox/satellite-v9');
    });
    var satelliteLabel = document.createElement('label');
    satelliteLabel.setAttribute('for', 'satellite');
    satelliteLabel.appendChild(document.createTextNode('Satellite'));
    mapMenu.appendChild(streetsInput);
    mapMenu.appendChild(streetsLabel);
    mapMenu.appendChild(satelliteInput);
    mapMenu.appendChild(satelliteLabel);
    var map = new mapboxgl.Map({
        container: mapDiv,
        style: 'mapbox://styles/mapbox/streets-v9',
        center: [longitude, latitude],
        zoom: 15,
        scrollZoom: false
    });
    map.addControl(new mapboxgl.NavigationControl());
    new mapboxgl.Marker(el, {offset: [-10, -20]}).setLngLat([longitude, latitude]).addTo(map);
    mapDiv.appendChild(mapMenu);
}
