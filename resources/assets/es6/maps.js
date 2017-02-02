//maps.js
import addMapTo from './mapbox-utils';

let mapDivs = document.querySelectorAll('.map');

for (var div of mapDivs) {
    addMapTo(div);
}
