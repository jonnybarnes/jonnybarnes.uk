//mapbox-utils.js
import mapboxgl from 'mapbox-gl/dist/mapbox-gl.js';
import parseLocation from './parse-location';
import selectPlaceInForm from './select-place';

mapboxgl.accessToken = 'pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiY2l2cDhjYW04MDAwcjJ0cG1uZnhqcm82ayJ9.qA2zeVA-nsoMh9IFrd5KQw';

//define some functions to be used in the default function.
const titlecase = (string) => {
    return string.split('-').map(([first,...rest]) => first.toUpperCase() + rest.join('').toLowerCase()).join(' ');
};

const addMapTypeOption = (map, menu, option, checked = false) => {
    let input = document.createElement('input');
    input.setAttribute('id', option);
    input.setAttribute('type', 'radio');
    input.setAttribute('name', 'toggle');
    input.setAttribute('value', option);
    if (checked == true) {
        input.setAttribute('checked', 'checked');
    }
    input.addEventListener('click', function () {
        let source = map.getSource('points');
        map.setStyle('mapbox://styles/mapbox/' + option + '-v9');
        map.on('style.load', function () {
            map.addLayer({
                'id': 'points',
                'type': 'symbol',
                'source': {
                    'type': 'geojson',
                    'data': source._data
                },
                'layout': {
                    'icon-image': '{icon}-15',
                    'text-field': '{title}',
                    'text-offset': [0, 1]
                }
            });
        });
    });
    let label = document.createElement('label');
    label.setAttribute('for', option);
    label.appendChild(document.createTextNode(titlecase(option)));
    menu.appendChild(input);
    menu.appendChild(label);
};

const makeMapMenu = (map) => {
    let mapMenu = document.createElement('div');
    mapMenu.classList.add('map-menu');
    addMapTypeOption(map, mapMenu, 'streets', true);
    addMapTypeOption(map, mapMenu, 'satellite-streets');
    return mapMenu;
};

//the main function
export default function addMap(div, position = null, places = null) {
    let dataLatitude = div.dataset.latitude;
    let dataLongitude = div.dataset.longitude;
    let data = window['geojson'+div.dataset.id];
    if (data == null) {
        data = {
            'type': 'FeatureCollection',
            'features': [{
                'type': 'Feature',
                'geometry': {
                    'type': 'Point',
                    'coordinates': [dataLongitude, dataLatitude]
                },
                'properties': {
                    'title': 'Current Location',
                    'icon': 'circle-stroked',
                    'uri': 'current-location'
                }
            }]
        };
    }
    if (places != null) {
        for (let place of places) {
            let placeLongitude = parseLocation(place.location).longitude;
            let placeLatitude = parseLocation(place.location).latitude;
            data.features.push({
                'type': 'Feature',
                'geometry': {
                    'type': 'Point',
                    'coordinates': [placeLongitude, placeLatitude]
                },
                'properties': {
                    'title': place.name,
                    'icon': 'circle',
                    'uri': place.slug
                }
            });
        }
    }
    if (position != null) {
        dataLongitude = position.coords.longitude;
        dataLatitude = position.coords.latitude;
    }
    let map = new mapboxgl.Map({
        container: div,
        style: 'mapbox://styles/mapbox/streets-v9',
        center: [dataLongitude, dataLatitude],
        zoom: 15
    });
    if (position == null) {
        map.scrollZoom.disable();
    }
    map.addControl(new mapboxgl.NavigationControl());
    div.appendChild(makeMapMenu(map));
    map.on('load', function () {
        map.addLayer({
            'id': 'points',
            'type': 'symbol',
            'source': {
                'type': 'geojson',
                'data': data
            },
            'layout': {
                'icon-image': '{icon}-15',
                'text-field': '{title}',
                'text-offset': [0, 1]
            }
        });
    });
    if (position != null) {
        map.on('click', function (e) {
            let features = map.queryRenderedFeatures(e.point, {
                layer: ['points']
            });
            // if there are features within the given radius of the click event,
            // fly to the location of the click event
            if (features.length) {
                // Get coordinates from the symbol and center the map on those coordinates
                map.flyTo({center: features[0].geometry.coordinates});
                selectPlaceInForm(features[0].properties.uri);
            }
        });
    }
    if (data.features && data.features.length > 1) {
        let bounds = new mapboxgl.LngLatBounds();
        for (let feature of data.features) {
            bounds.extend(feature.geometry.coordinates);
        }
        map.fitBounds(bounds, { padding: 65});
    }

    return map;
}
