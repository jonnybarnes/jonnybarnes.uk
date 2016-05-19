//This code runs on page load and looks for <div class="map">, then adds map
var mapDivs = document.querySelectorAll('.map');
for(var i = 0; i < mapDivs.length; i++) {
    var mapDiv = mapDivs[i];
    var latitude = mapDiv.dataset.latitude;
    var longitude  = mapDiv.dataset.longitude;
    L.mapbox.accessToken = 'pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiVlpndW1EYyJ9.aP9fxAqLKh7lj0LpFh5k1w';
    var map = L.mapbox.map(mapDiv, 'jonnybarnes.gnoihnim')
        .setView([latitude, longitude], 15)
        .addLayer(L.mapbox.tileLayer('jonnybarnes.gnoihnim', {
        detectRetina: true,
    }));
    var marker = L.marker([latitude, longitude]).addTo(map);
    map.scrollWheelZoom.disable();
}
