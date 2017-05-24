//maps.js
import addMap from './mapbox-utils';

let mapDivs = document.querySelectorAll('.map');

for (var div of mapDivs) {
    addMap(div);
}
