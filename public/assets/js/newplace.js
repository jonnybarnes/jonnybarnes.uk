var button = document.querySelector('#locate');

if (button.addEventListener) {
    button.addEventListener('click', getLocation);
} else {
    button.attachEvent('onclick', getLocation);
}

function getLocation() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            updateForm(position.coords.latitude, position.coords.longitude);
            addMap(position.coords.latitude, position.coords.longitude);
        });
    } else {
        console.log('I need to do something when geoloaction isn’t available.');
    }
}

function updateForm(latitude, longitude) {
    var inputLatitude = document.querySelector('#latitude');
    var inputLongitude = document.querySelector('#longitude');
    inputLatitude.value = latitude;
    inputLongitude.value = longitude;
}

function addMap(latitude, longitude) {
    var form = document.querySelector('form');
    var div = document.createElement('div');
    div.setAttribute('id', 'map');
    form.appendChild(div);
    L.mapbox.accessToken = 'pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiVlpndW1EYyJ9.aP9fxAqLKh7lj0LpFh5k1w';
    var map = L.mapbox.map('map', 'jonnybarnes.gnoihnim')
        .setView([latitude, longitude], 15)
        .addLayer(L.mapbox.tileLayer('jonnybarnes.gnoihnim', {
            detectRetina: true,
        }));
    var marker = L.marker([latitude, longitude], {
        draggable: true,
    }).addTo(map);
    marker.on('dragend', function () {
        var markerLocation = marker.getLatLng();
        updateForm(markerLocation.lat, markerLocation.lng);
    });
}
