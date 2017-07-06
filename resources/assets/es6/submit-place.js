//submit-place.js

import alertify from 'alertify.js';

export default function submitNewPlace(map) {
    //create the form data to send
    let formData = new FormData();
    formData.append('place-name', document.querySelector('#place-name').value);
    formData.append('place-description', document.querySelector('#place-description').value);
    formData.append('place-latitude', document.querySelector('#place-latitude').value);
    formData.append('place-longitude', document.querySelector('#place-longitude').value);
    //post the new place
    fetch('/micropub/places', {
        //send cookies with the request
        credentials: 'same-origin',
        method: 'post',
        body: formData
    }).then(function (response) {
        return response.json();
    }).then(function (placeJson) {
        if (placeJson.error === true) {
            throw new Error(placeJson.error_description);
        }
        //remove un-needed form elements
        let form = document.querySelector('fieldset');
        //iterate through labels and remove parent div elements
        let labels = document.querySelectorAll('.place-label');
        for (let label of labels) {
            form.removeChild(label.parentNode);
        }
        form.removeChild(document.querySelector('#place-submit'));
        let newPlaceButton = document.querySelector('#create-new-place');
        //in order to remove a DOM Node, you need to run removeChild on the parent Node
        newPlaceButton.parentNode.removeChild(newPlaceButton);
        //remove current location from map
        let source = map.getSource('points');
        let newFeatures = source._data.features.filter(function (item) {
            return item.properties.title != 'Current Location';
        });
        //add new place to map
        newFeatures.push({
            'type': 'Feature',
            'geometry': {
                'type': 'Point',
                'coordinates': [placeJson.longitude, placeJson.latitude]
            },
            'properties': {
                'title': placeJson.name,
                'icon': 'circle',
                'uri': placeJson.uri
            }
        });
        let newSource = {
            'type': 'FeatureCollection',
            'features': newFeatures
        };
        map.getSource('points').setData(newSource);
        //add new place to select menu
        let selectElement = document.querySelector('select');
        let newlyCreatedPlaceOption = document.createElement('option');
        newlyCreatedPlaceOption.setAttribute('value', placeJson.uri);
        newlyCreatedPlaceOption.appendChild(document.createTextNode(placeJson.name));
        newlyCreatedPlaceOption.dataset.latitude = placeJson.latitude;
        newlyCreatedPlaceOption.dataset.longitude = placeJson.longitude;
        selectElement.appendChild(newlyCreatedPlaceOption);
        document.querySelector('select [value="' + placeJson.uri + '"]').selected = true;
    }).catch(function (placeError) {
        alertify.reset();
        alertify.error(placeError);
    });
}
