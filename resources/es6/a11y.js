//a11y.css.js

let checkbox = document.querySelector('input[name="a11y.css"]');

checkbox.addEventListener('change', function () {
    if (this.checked) {
        addA11yCss();
    } else {
        removeA11yCss();
    }
});

function addA11yCss() {
    let exists = a11yCssExists();
    if (exists == false) {
        //add a11y.css link
        let link = document.createElement('link');
        link.setAttribute('rel', 'stylesheet');
        link.setAttribute('href', '/assets/frontend/a11y.css/a11y-en.css');
        let head = document.querySelector('head');
        head.appendChild(link);
    }
}

function removeA11yCss() {
    let exists = a11yCssExists();
    if (exists == true) {
        //remove a11y.css link
        let link = document.querySelector('link[href="/assets/frontend/a11y.css/a11y-en.css"]');
        let head = document.querySelector('head');
        head.removeChild(link);
    }
}

function a11yCssExists() {
    let css = document.querySelectorAll('link[rel=stylesheet]');
    let exists = false;
    for (let link of css) {
        if (link.attributes.href.nodeValue == '/assets/frontend/a11y.css/a11y-en.css') {
            exists = true;
        }
    }

    return exists;
}
