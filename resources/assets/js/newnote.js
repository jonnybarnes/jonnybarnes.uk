/* global mapboxgl, alertify */
if ('geolocation' in navigator) {
    var button = document.querySelector('#locate');
    if (button.addEventListener) {
        //if we have javascript, event listeners and geolocation, make the locate
        //button clickable and add event
        button.disabled = false;
        button.addEventListener('click', getLocation);
    }
}

function getLocation() {
    navigator.geolocation.getCurrentPosition(function (position) {
        //the locate button has been clicked so add the places/map
        addPlacesMap(position.coords.latitude, position.coords.longitude, position.coords.accuracy);
    });
}

function addPlacesMap(latitude, longitude, uncertainty) {
    //get the nearby places
    fetch('/places/near/' + latitude + '/' + longitude + '?u=' + uncertainty, {
        credentials: 'same-origin',
        method: 'get'
    }).then(function (response) {
        return response.json();
    }).then(function (j) {
        if (j.error === true) {
            alertify.reset();
            alertify.error(j.error_description);
        }
        if (j.places.length > 0) {
            var i;
            var places = [];
            for (i = 0; i < j.places.length; ++i) {
                var latlng = parseLocation(j.places[i].location);
                var name = j.places[i].name;
                var uri = j.places[i].uri;
                places.push([name, uri, latlng[0], latlng[1]]);
            }
            //add a map with the nearby places
            addMap(latitude, longitude, places);
        } else {
            //add a map with just current location
            addMap(latitude, longitude);
        }
    }).catch(function (err) {
        console.error(err);
    });
}

function addMap(latitude, longitude, places) {
    //make places null if not supplied
    if (arguments.length == 2) {
        places = null;
    }
    // the form has a fieldset element that we are actually targetting
    var form = document.querySelector('.note-ui');
    var mapDiv = document.createElement('div');
    mapDiv.classList.add('map');
    //add the map div
    form.appendChild(mapDiv);
    //set up the mapbox gl map
    mapboxgl.accessToken = 'pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiY2l2cDhjYW04MDAwcjJ0cG1uZnhqcm82ayJ9.qA2zeVA-nsoMh9IFrd5KQw';
    var map = new mapboxgl.Map({
        container: mapDiv,
        style: 'mapbox://styles/mapbox/streets-v9',
        center: [longitude, latitude],
        zoom: 15
    });
    map.addControl(new mapboxgl.NavigationControl());
    //create the current location marker
    var el = document.createElement('div');
    el.classList.add('marker');
    //create the map style menu
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
    //add the map menu
    mapDiv.appendChild(mapMenu);
    //add a marker for the current location
    new mapboxgl.Marker(el, {offset: [-10, -20]}).setLngLat([longitude, latitude]).addTo(map);
    //create containing div for flexbox
    var containingDiv = document.createElement('div');
    //create the <select> element and give it a no location default
    var selectEl = document.createElement('select');
    selectEl.setAttribute('name', 'location');
    var noLocation = document.createElement('option');
    noLocation.setAttribute('value', 'no-location');
    noLocation.appendChild(document.createTextNode('Don’t send location'));
    selectEl.appendChild(noLocation);
    var geoLocation = document.createElement('option');
    geoLocation.setAttribute('selected', 'selected');
    geoLocation.setAttribute('id', 'option-coords');
    geoLocation.setAttribute('value', 'geo:' + latitude + ',' + longitude);
    geoLocation.dataset.latitude = latitude;
    geoLocation.dataset.longitude = longitude;
    geoLocation.appendChild(document.createTextNode('Send co-ordinates'));
    selectEl.appendChild(geoLocation);
    containingDiv.appendChild(selectEl);
    form.insertBefore(containingDiv, mapDiv);
    if (places !== null) {
        //add the places both to the map and <select>
        places.forEach(function (item) {
            var option = document.createElement('option');
            option.setAttribute('value', item[1]);
            var text = document.createTextNode(item[0]);
            option.appendChild(text);
            option.dataset.latitude = item[2];
            option.dataset.longitude = item[3];
            selectEl.appendChild(option);
            var placeMarkerIcon = document.createElement('div');
            placeMarkerIcon.classList.add('marker');
            new mapboxgl.Marker(placeMarkerIcon, {offset: [-10, -20]}).setLngLat([item[3], item[2]]).addTo(map);
            placeMarkerIcon.addEventListener('click', function () {
                map.flyTo({
                    center: [
                        item[3],
                        item[2]
                    ]
                });
                selectPlace(item[1]);
            });
        });
        //add an event listener
        selectEl.addEventListener('change', function () {
            if (selectEl.value !== 'no-location') {
                var placeLat = selectEl[selectEl.selectedIndex].dataset.latitude;
                var placeLon = selectEl[selectEl.selectedIndex].dataset.longitude;
                map.flyTo({
                    center: [
                        placeLon,
                        placeLat
                    ]
                });
            }
        });
    }
    //add a button to add a new place
    var newLocButton = document.createElement('button');
    newLocButton.setAttribute('type', 'button');
    newLocButton.setAttribute('id', 'create-new-place');
    newLocButton.appendChild(document.createTextNode('Create New Place?'));
    //the event listener
    newLocButton.addEventListener('click', function() {
        //add the form elements
        var nameDiv = document.createElement('div');
        var nameLabel = document.createElement('label');
        nameLabel.setAttribute('for', 'place-name');
        nameLabel.classList.add('place-label');
        nameLabel.appendChild(document.createTextNode('Place Name:'));
        var nameEl = document.createElement('input');
        nameEl.setAttribute('placeholder', 'Name');
        nameEl.setAttribute('name', 'place-name');
        nameEl.setAttribute('id', 'place-name');
        nameEl.setAttribute('type', 'text');
        nameDiv.appendChild(nameLabel);
        nameDiv.appendChild(nameEl);
        var descDiv = document.createElement('div');
        var descLabel = document.createElement('label');
        descLabel.setAttribute('for', 'place-description');
        descLabel.classList.add('place-label');
        descLabel.appendChild(document.createTextNode('Place Description:'));
        var descEl = document.createElement('input');
        descEl.setAttribute('placeholder', 'Description');
        descEl.setAttribute('name', 'place-description');
        descEl.setAttribute('id', 'place-description');
        descEl.setAttribute('type', 'text');
        descDiv.appendChild(descLabel);
        descDiv.appendChild(descEl);
        var latDiv = document.createElement('div');
        var latLabel = document.createElement('label');
        latLabel.setAttribute('for', 'place-latitude');
        latLabel.classList.add('place-label');
        latLabel.appendChild(document.createTextNode('Place Latitude:'));
        var latEl = document.createElement('input');
        latEl.setAttribute('name', 'place-latitude');
        latEl.setAttribute('id', 'place-latitude');
        latEl.setAttribute('type', 'text');
        latEl.value = getLatitudeFromMapbox(map.getCenter());
        latDiv.appendChild(latLabel);
        latDiv.appendChild(latEl);
        var lonDiv = document.createElement('div');
        var lonLabel = document.createElement('label');
        lonLabel.setAttribute('for', 'place-longitude');
        lonLabel.classList.add('place-label');
        lonLabel.appendChild(document.createTextNode('Place Longitude:'));
        var lonEl = document.createElement('input');
        lonEl.setAttribute('name', 'place-longitude');
        lonEl.setAttribute('id', 'place-longitude');
        lonEl.setAttribute('type', 'text');
        lonEl.value = getLongitudeFromMapbox(map.getCenter());
        lonDiv.appendChild(lonLabel);
        lonDiv.appendChild(lonEl);
        var placeSubmit = document.createElement('button');
        placeSubmit.setAttribute('id', 'place-submit');
        placeSubmit.setAttribute('value', 'Submit New Place');
        placeSubmit.setAttribute('name', 'place-submit');
        placeSubmit.setAttribute('type', 'button');
        placeSubmit.appendChild(document.createTextNode('Submit New Place'));
        form.appendChild(nameDiv);
        form.appendChild(descDiv);
        form.appendChild(latDiv);
        form.appendChild(lonDiv);
        form.appendChild(placeSubmit);
        //the event listener for the new place form
        placeSubmit.addEventListener('click', function () {
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
        });
    });
    containingDiv.appendChild(newLocButton);
}

function parseLocation(point) {
    var re = /\((.*)\)/;
    var resultArray = re.exec(point);
    var location = resultArray[1].split(' ');

    return [location[1], location[0]];
}

function selectPlace(uri) {
    document.querySelector('select [value="' + uri + '"]').selected = true;
}

function getLatitudeFromMapbox(lnglat) {
    var resultArray = /\((.*)\)/.exec(lnglat);
    var location = resultArray[1].split(' ');

    return location[1];
}

function getLongitudeFromMapbox(lnglat) {
    var resultArray = /\((.*)\)/.exec(lnglat);
    var location = resultArray[1].split(' ');

    return location[0].replace(',', '');
}
