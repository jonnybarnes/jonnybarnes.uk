//maps.js
import addMap from './mapbox-utils';

let mapDivs = document.querySelectorAll('.map');

for (let div of mapDivs) {
    addMap(div);
}
