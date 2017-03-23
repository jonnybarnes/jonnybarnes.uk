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
    });

    return newLocationButton;
}
