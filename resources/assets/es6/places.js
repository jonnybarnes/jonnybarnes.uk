//places.js

import addMap from './mapbox-utils';
import getIcon from './edit-place-icon';

let div = document.querySelector('.map');
let map = addMap(div);

let selectElem = document.querySelector('select[name="icon"]');
selectElem.addEventListener('click', function () {
    let source = map.getSource('points');
    let newIcon = getIcon();
    if (source._data.features[0].properties.icon != newIcon) {
        source._data.features[0].properties.icon = newIcon;
        map.getSource('points').setData(source._data);
    }
});
