//newnote.js

import enableLocateButton from './newnote-button';
import persistFormData from './persist-form';

let button = document.querySelector('#locate');
enableLocateButton(button);
persistFormData();
