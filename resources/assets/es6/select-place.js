//select-place.js

export default function selectPlaceInForm(uri) {
    if (document.querySelector('select')) {
        if (uri == 'current-location') {
            document.querySelector('select [id="option-coords"]').selected = true
        } else {
            document.querySelector('select [value="' + uri + '"]').selected = true
        }
    }
}
