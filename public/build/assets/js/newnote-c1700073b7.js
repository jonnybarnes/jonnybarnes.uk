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
        addPlaces(position.coords.latitude, position.coords.longitude);
    });
}

function addPlaces(latitude, longitude) {
    //get the nearby places
    fetch('/places/near/' + latitude + '/' + longitude, {
        credentials: 'same-origin',
        method: 'get'
    }).then(function (response) {
        return response.json();
    }).then(function (j) {
        if (j.length > 0) {
            var i;
            var places = [];
            for (i = 0; i < j.length; ++i) {
                var latlng = parseLocation(j[i].location);
                var name = j[i].name;
                var slug = j[i].slug;
                places.push([name, slug, latlng[0], latlng[1]]);
            }
            //add a map with the nearby places
            addMap(latitude, longitude, places);
        } else {
            //add a map with just current location
            addMap(latitude, longitude);
        }
    }).catch(function (err) {
        console.log(err);
    });
}

function addMap(latitude, longitude, places) {
    //make places null if not supplied
    if (arguments.length == 2) {
        places = null;
    }
    var form = button.parentNode;
    var div = document.createElement('div');
    div.setAttribute('id', 'map');
    //add the map div
    form.appendChild(div);
    L.mapbox.accessToken = 'pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiVlpndW1EYyJ9.aP9fxAqLKh7lj0LpFh5k1w';
    var map = L.mapbox.map('map', 'jonnybarnes.gnoihnim')
        .setView([latitude, longitude], 15)
        .addLayer(L.mapbox.tileLayer('jonnybarnes.gnoihnim', {
            detectRetina: true,
        }));
    //add a marker for the current location
    var marker = L.marker([latitude, longitude], {
        draggable: true,
    }).addTo(map);
    //when the location marker is dragged, if the new place form elements exist
    //update the lat/lng values
    marker.on('dragend', function () {
        var placeFormLatitude = document.querySelector('#place-latitude');
        if (placeFormLatitude !== null) {
            placeFormLatitude.value = getLatitudeFromMapboxMarker(marker.getLatLng());
        }
        var placeFormLongitude = document.querySelector('#place-longitude');
        if (placeFormLongitude !== null) {
            placeFormLongitude.value = getLongitudeFromMapboxMarker(marker.getLatLng());
        }
    });
    //create the <select> element and give it a no location default
    var selectEl = document.createElement('select');
    selectEl.setAttribute('name', 'location');
    var noLocation = document.createElement('option');
    noLocation.setAttribute('selected', 'selected');
    noLocation.setAttribute('value', 'no-location');
    noLocText = document.createTextNode('Select no location');
    noLocation.appendChild(noLocText);
    selectEl.appendChild(noLocation);
    form.insertBefore(selectEl, div);
    if (places !== null) {
        //add the places both to the map and <select>
        places.forEach(function (item, index, array) {
            var option = document.createElement('option');
            option.setAttribute('value', item[1]);
            var text = document.createTextNode(item[0]);
            option.appendChild(text);
            option.dataset.latitude = item[2];
            option.dataset.longitude = item[3];
            selectEl.appendChild(option);
            var placeMarker = L.marker([item[2], item[3]], {
                icon: L.mapbox.marker.icon({
                    'marker-size': 'large',
                    'marker-symbol': 'building',
                    'marker-color': '#fa0'
                })
            }).addTo(map);
            var name = 'Name: ' + item[0];
            placeMarker.bindPopup(name, {
                closeButton: true
            });
            placeMarker.on('click', function (e) {
                map.panTo([item[2], item[3]]);
                selectPlace(item[1]);
            });
        });
        //add an event listener
        selectEl.addEventListener('change', function () {
            if (selectEl.value !== 'no-location') {
                var placeLat = selectEl[selectEl.selectedIndex].dataset.latitude;
                var placeLon = selectEl[selectEl.selectedIndex].dataset.longitude;
                map.panTo([placeLat, placeLon]);
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
        var nameLabel = document.createElement('label');
        nameLabel.setAttribute('for', 'place-name');
        nameLabel.classList.add('place-label')
        nameLabel.appendChild(document.createTextNode('Place Name:'));
        var nameEl = document.createElement('input');
        nameEl.setAttribute('placeholder', 'Name');
        nameEl.setAttribute('name', 'place-name');
        nameEl.setAttribute('id', 'place-name');
        nameEl.setAttribute('type', 'text');
        var descLabel = document.createElement('label');
        descLabel.setAttribute('for', 'place-description');
        descLabel.classList.add('place-label');
        descLabel.appendChild(document.createTextNode('Place Description:'));
        var descEl = document.createElement('input');
        descEl.setAttribute('placeholder', 'Description');
        descEl.setAttribute('name', 'place-description');
        descEl.setAttribute('id', 'place-description');
        descEl.setAttribute('type', 'text');
        var latLabel = document.createElement('label');
        latLabel.setAttribute('for', 'place-latitude');
        latLabel.classList.add('place-label');
        latLabel.appendChild(document.createTextNode('Place Latitude:'));
        var latEl = document.createElement('input');
        latEl.setAttribute('name', 'place-latitude');
        latEl.setAttribute('id', 'place-latitude');
        latEl.setAttribute('type', 'text');
        latEl.value = getLatitudeFromMapboxMarker(marker.getLatLng());
        var lonLabel = document.createElement('label');
        lonLabel.setAttribute('for', 'place-longitude');
        lonLabel.classList.add('place-label');
        lonLabel.appendChild(document.createTextNode('Place Longitude:'));
        var lonEl = document.createElement('input');
        lonEl.setAttribute('name', 'place-longitude');
        lonEl.setAttribute('id', 'place-longitude');
        lonEl.setAttribute('type', 'text');
        lonEl.value = getLongitudeFromMapboxMarker(marker.getLatLng());
        var placeSubmit = document.createElement('button');
        placeSubmit.setAttribute('id', 'place-submit');
        placeSubmit.setAttribute('value', 'Submit New Place');
        placeSubmit.setAttribute('name', 'place-submit');
        placeSubmit.setAttribute('type', 'button');
        placeSubmit.appendChild(document.createTextNode('Submit New Place'));
        form.appendChild(nameLabel);
        form.appendChild(nameEl);
        form.appendChild(descLabel);
        form.appendChild(descEl);
        form.appendChild(latLabel);
        form.appendChild(latEl);
        form.appendChild(lonLabel);
        form.appendChild(lonEl);
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
            .then(status)
            .then(json)
            .then(function (placeJson) {
                //create the slug from the url
                var urlParts = placeJson.split('/');
                var slug = urlParts.pop();
                //remove un-needed form elements
                form.removeChild(document.querySelector('#place-name'));
                form.removeChild(document.querySelector('#place-description'));
                form.removeChild(document.querySelector('#place-latitude'));
                form.removeChild(document.querySelector('#place-longitude'));
                var labels = document.querySelectorAll('.place-label');
                for (var label of labels) {
                    form.removeChild(label);
                }
                form.removeChild(document.querySelector('#place-submit'));
                form.removeChild(document.querySelector('#create-new-place'));
                //remove location marker
                map.removeLayer(marker);
                //add place marker
                var newOption = document.createElement('option');
                newOption.setAttribute('value', slug);
                newOption.appendChild(document.createTextNode(placeJson['name']));
                newOption.dataset.latitude = placeJson['latitude'];
                newOption.dataset.longitude = placeJson['longitude'];
                selectEl.appendChild(newOption);
                var newPlaceMarker = L.marker([placeJson['latitude'], placeJson['longitude']], {
                    icon: L.mapbox.marker.icon({
                        'marker-size': 'large',
                        'marker-symbol': 'building',
                        'marker-color': '#fa0'
                    })
                }).addTo(map);
                var newName = 'Name: ' + placeJson['name'];
                newPlaceMarker.bindPopup(newName, {
                    closeButton: true
                });
                newPlaceMarker.on('click', function (e) {
                    map.panTo([placeJson['latitude'], placeJson['longitude']]);
                    selectPlace(slug);
                });
                //make selected
                selectPlace(slug);
            }).catch(function (placeError) {
                console.log(placeError);
            });
        })
    });
    form.insertBefore(newLocButton, div);
}

function parseLocation(point) {
    var re = /\((.*)\)/;
    var resultArray = re.exec(point);
    var location = resultArray[1].split(' ');

    return [location[1], location[0]];
}

function selectPlace(slug) {
    document.querySelector('select [value=' + slug + ']').selected = true;
}

function getLatitudeFromMapboxMarker(latlng) {
    var resultArray = /\((.*)\)/.exec(latlng);
    var location = resultArray[1].split(' ');

    return location[0].replace(',', '');
}

function getLongitudeFromMapboxMarker(latlng) {
    var resultArray = /\((.*)\)/.exec(latlng);
    var location = resultArray[1].split(' ');

    return location[1];
}

function status(response) {
    if (response.status >= 200 && response.status < 300) {
        return Promise.resolve(response);
    } else {
        return Promise.reject(new Error(response.statusText));
    }
}

function json(response) {
    return response.json();
}
