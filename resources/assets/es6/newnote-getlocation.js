//newnote-getlocation.js

import addMapWithPlaces from './nearby-places';

export default function getLocation() {
    let container = document.querySelector('fieldset');
    let mapDiv = document.createElement('div');
    mapDiv.classList.add('map');
    container.appendChild(mapDiv);
    navigator.geolocation.getCurrentPosition(function (position) {
        mapDiv.dataset.latitude = position.coords.latitude;
        mapDiv.dataset.longitude = position.coords.longitude;
        mapDiv.dataset.accuracy = position.coords.accuracy;
        addMapWithPlaces(position);
    });
}
