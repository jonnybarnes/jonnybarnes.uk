//newplace-micropub.js

import submitNewPlace from './submit-place';

export default function makeNewPlaceForm(map) {
    //add a button to add a new place
    let newLocationButton = document.createElement('button');
    newLocationButton.setAttribute('type', 'button');
    newLocationButton.setAttribute('id', 'create-new-place');
    newLocationButton.appendChild(document.createTextNode('Create New Place?'));
    //the event listener
    newLocationButton.addEventListener('click', function() {
        //add the form elements
        let newPlaceNameDiv = document.createElement('div');
        let newPlaceNameLabel = document.createElement('label');
        newPlaceNameLabel.setAttribute('for', 'place-name');
        newPlaceNameLabel.classList.add('place-label');
        newPlaceNameLabel.appendChild(document.createTextNode('Name:'));
        let newPlaceNameInput = document.createElement('input');
        newPlaceNameInput.setAttribute('placeholder', 'Name');
        newPlaceNameInput.setAttribute('name', 'place-name');
        newPlaceNameInput.setAttribute('id', 'place-name');
        newPlaceNameInput.setAttribute('type', 'text');
        newPlaceNameDiv.appendChild(newPlaceNameLabel);
        newPlaceNameDiv.appendChild(newPlaceNameInput);
        let newPlaceDescDiv = document.createElement('div');
        let newPlaceDescLabel = document.createElement('label');
        newPlaceDescLabel.setAttribute('for', 'place-description');
        newPlaceDescLabel.classList.add('place-label');
        newPlaceDescLabel.appendChild(document.createTextNode('Description:'));
        let newPlaceDescInput = document.createElement('input');
        newPlaceDescInput.setAttribute('placeholder', 'Description');
        newPlaceDescInput.setAttribute('name', 'place-description');
        newPlaceDescInput.setAttribute('id', 'place-description');
        newPlaceDescInput.setAttribute('type', 'text');
        newPlaceDescDiv.appendChild(newPlaceDescLabel);
        newPlaceDescDiv.appendChild(newPlaceDescInput);
        let newPlaceLatitudeDiv = document.createElement('div');
        var newPlaceLatitudeLabel = document.createElement('label');
        newPlaceLatitudeLabel.setAttribute('for', 'place-latitude');
        newPlaceLatitudeLabel.classList.add('place-label');
        newPlaceLatitudeLabel.appendChild(document.createTextNode('Latitude:'));
        let newPlaceLatitudeInput = document.createElement('input');
        newPlaceLatitudeInput.setAttribute('name', 'place-latitude');
        newPlaceLatitudeInput.setAttribute('id', 'place-latitude');
        newPlaceLatitudeInput.setAttribute('type', 'text');
        newPlaceLatitudeInput.value = map.getCenter().lat;
        newPlaceLatitudeDiv.appendChild(newPlaceLatitudeLabel);
        newPlaceLatitudeDiv.appendChild(newPlaceLatitudeInput);
        let newPlaceLongitudeDiv = document.createElement('div');
        let newPlaceLongitudeLabel = document.createElement('label');
        newPlaceLongitudeLabel.setAttribute('for', 'place-longitude');
        newPlaceLongitudeLabel.classList.add('place-label');
        newPlaceLongitudeLabel.appendChild(document.createTextNode('Longitude:'));
        let newPlaceLongitudeInput = document.createElement('input');
        newPlaceLongitudeInput.setAttribute('name', 'place-longitude');
        newPlaceLongitudeInput.setAttribute('id', 'place-longitude');
        newPlaceLongitudeInput.setAttribute('type', 'text');
        newPlaceLongitudeInput.value = map.getCenter().lng;
        newPlaceLongitudeDiv.appendChild(newPlaceLongitudeLabel);
        newPlaceLongitudeDiv.appendChild(newPlaceLongitudeInput);
        let newPlaceSubmit = document.createElement('button');
        newPlaceSubmit.setAttribute('id', 'place-submit');
        newPlaceSubmit.setAttribute('name', 'place-submit');
        newPlaceSubmit.setAttribute('type', 'button');
        newPlaceSubmit.appendChild(document.createTextNode('Submit New Place'));
        newPlaceSubmit.addEventListener('click', function () {
            submitNewPlace(map);
        });
        let form = document.querySelector('fieldset');
        form.appendChild(newPlaceNameDiv);
        form.appendChild(newPlaceDescDiv);
        form.appendChild(newPlaceLatitudeDiv);
        form.appendChild(newPlaceLongitudeDiv);
        form.appendChild(newPlaceSubmit);
        //the event listener for the new place form
        /*placeSubmit.addEventListener('click', function () {
            //create the form data to send
            var formData = new FormData();
            formData.append('place-name', document.querySelector('#place-name').value);
            formData.append('place-description', document.querySelector('#place-description').value);
            formData.append('place-latitude', document.querySelector('#place-latitude').value);
            formData.append('place-longitude', document.querySelector('#place-longitude').value);
            //post the new place
            fetch('/places/new', {
                //send cookies with the request
                credentials: 'same-origin',
                method: 'post',
                body: formData
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (placeJson) {
                if (placeJson.error === true) {
                    throw new Error(placeJson.error_description);
                }
                //remove un-needed form elements
                //iterate through labels and remove parent div elements
                var labels = document.querySelectorAll('.place-label');
                for (var i = 0; i < labels.length; ++i) {
                    form.removeChild(labels[i].parentNode);
                }
                form.removeChild(document.querySelector('#place-submit'));
                var newPlaceButton = document.querySelector('#create-new-place');
                //in order to remove a DOM Node, you need to run removeChild on the parent Node
                newPlaceButton.parentNode.removeChild(newPlaceButton);
                //add place marker
                var newOption = document.createElement('option');
                newOption.setAttribute('value', placeJson.uri);
                newOption.appendChild(document.createTextNode(placeJson.name));
                newOption.dataset.latitude = placeJson.latitude;
                newOption.dataset.longitude = placeJson.longitude;
                selectEl.appendChild(newOption);
                var newPlaceMarkerIcon = document.createElement('div');
                newPlaceMarkerIcon.classList.add('marker');
                new mapboxgl.Marker(newPlaceMarkerIcon, {offset: [-10, -20]}).setLngLat([placeJson.longitude, placeJson.latitude]).addTo(map);
                map.flyTo({center: [placeJson.longitude, placeJson.latitude]});

                newPlaceMarkerIcon.addEventListener('click', function () {
                    map.flyTo({center: [placeJson.longitude, placeJson.latitude]});
                    selectPlace(placeJson.uri);
                });
                //make selected
                selectPlace(placeJson.uri);
            }).catch(function (placeError) {
                alertify.reset();
                alertify.error(placeError);
            });
        });*/
    });

    return newLocationButton;
}
