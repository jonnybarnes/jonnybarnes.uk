//nearby-places.js

import alertify from 'alertify.js';
import addMap from './mapbox-utils';
import parseLocation from './parse-location';
import makeNewPlaceForm from './newplace-micropub';

const makeOptionsForForm = (map, position, places = null) => {
    //create the <select> element and give it a no location default
    let selectElement = document.createElement('select');
    selectElement.setAttribute('name', 'location');
    let noLocationOption = document.createElement('option');
    noLocationOption.setAttribute('selected', 'selected');
    noLocationOption.setAttribute('value', 'no-location');
    noLocationOption.appendChild(document.createTextNode('Don’t send location'));
    selectElement.appendChild(noLocationOption);
    let geoLocationOption = document.createElement('option');
    geoLocationOption.setAttribute('id', 'option-coords');
    geoLocationOption.setAttribute('value', 'geo:' + position.coords.latitude + ',' + position.coords.longitude);
    geoLocationOption.dataset.latitude = position.coords.latitude;
    geoLocationOption.dataset.longitude = position.coords.longitude;
    geoLocationOption.appendChild(document.createTextNode('Send co-ordinates'));
    selectElement.appendChild(geoLocationOption);
    if (places != null) {
        for (let place of places) {
            let parsedCoords = parseLocation(place.location);
            let option = document.createElement('option');
            option.setAttribute('value', place.slug);
            option.dataset.latitude = parsedCoords.latitude;
            option.dataset.longitude = parsedCoords.longitude;
            option.appendChild(document.createTextNode(place.name));
            selectElement.appendChild(option);
        }
    }
    //add an event listener
    selectElement.addEventListener('change', function () {
        if (selectElement.value !== 'no-location') {
            let optionLatitude = selectElement[selectElement.selectedIndex].dataset.latitude;
            let optionLongitude = selectElement[selectElement.selectedIndex].dataset.longitude;
            map.flyTo({center: [optionLongitude, optionLatitude]});
        }
    });

    return selectElement;
};

//position is output of navigator.geolocation call
export default function addMapWithPlaces(div, position) {
    fetch('/micropub/places?latitude=' + position.coords.latitude + '&longitude=' + position.coords.longitude + '&u=' + position.coords.accuracy, {
        credentials: 'same-origin',
        method: 'get'
    }).then(function (response) {
        if (response.ok) {
            return response.json();
        } else {
            alertify.reset();
            alertify.error('Non OK response');
        }
    }).then(function (json) {
        if (json.error == true) {
            alertify.reset();
            alertify.error(json.error_description);
        }
        let places = null;
        if (json.places.length > 0) {
            places = json.places;
        }
        let map = addMap(div, position, places);
        //create a containting div for flexbox styling purposes
        let flexboxDiv = document.createElement('div');
        let options = makeOptionsForForm(map, position, places);
        flexboxDiv.appendChild(options);
        let newPlaceForm = makeNewPlaceForm(map);
        flexboxDiv.appendChild(newPlaceForm);
        let form = document.querySelector('fieldset');
        form.insertBefore(flexboxDiv, document.querySelector('.map'));
    }).catch(function (error) {
        console.error(error);
    });
}
