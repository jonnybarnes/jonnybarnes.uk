//links.js

let youtubeRegex = /watch\?v=([A-Za-z0-9\-_]+)\b/;
let spotifyRegex = /https\:\/\/play\.spotify\.com\/(.*)\b/;

let notes = document.querySelectorAll('.e-content');

for (let note of notes) {
    let ytid = note.textContent.match(youtubeRegex);
    if (ytid) {
        let ytcontainer = document.createElement('div');
        ytcontainer.classList.add('container');
        let ytiframe = document.createElement('iframe');
        ytiframe.classList.add('youtube');
        ytiframe.setAttribute('src', 'https://www.youtube.com/embed/' + ytid[1]);
        ytiframe.setAttribute('frameborder', 0);
        ytiframe.setAttribute('allowfullscreen', 'true');
        ytcontainer.appendChild(ytiframe);
        note.appendChild(ytcontainer);
    }
    let spotifyid = note.textContent.match(spotifyRegex);
    if (spotifyid) {
        let sid = spotifyid[1].replace('/', ':');
        let siframe = document.createElement('iframe');
        siframe.classList.add('spotify');
        siframe.setAttribute('src', 'https://embed.spotify.com/?uri=spotify:' + sid);
        siframe.setAttribute('frameborder', 0);
        siframe.setAttribute('allowtransparency', 'true');
        note.appendChild(siframe);
    }
}
