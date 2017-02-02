//newnote-button.js

import getLocation from './newnote-getlocation';

export default function enableLocateButton(button) {
    if ('geolocation' in navigator) {
        if (button.addEventListener) {
            //if we have javascript, event listeners and geolocation
            //make the locate button clickable and add event
            button.disabled = false;
            button.addEventListener('click', getLocation);
        }
    }
}
