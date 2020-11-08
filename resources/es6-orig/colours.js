//colours.js

let link = document.querySelector('#colourScheme');

let css = link.getAttribute('href').split('/').pop();

// update selected item in colour scheme list
document.querySelector('#colourSchemeSelect [value="' + css + '"]').selected = true;

// fix form
let form = document.getElementById('colourSchemeForm');
let btn = form.querySelector('button');
btn.addEventListener('click', function (event) {
    event.preventDefault();
    let newCss = document.getElementById('colourSchemeSelect').value;
    let css = link.getAttribute('href');
    let parts = css.split('/');
    parts.pop();
    parts.push(newCss);
    link.setAttribute('href', parts.join('/'));
    let formData = new FormData(form);
    fetch('/update-colour-scheme', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    }).catch(function (error) {
        console.warn(error);
    });
});
