/* global Autolinker */
//the autlinker object
var autolinker = new Autolinker();

//the youtube regex
var ytidregex = /watch\?v=([A-Za-z0-9\-_]+)/;

var spotifyregex = /https\:\/\/play\.spotify\.com\/(.*)\b/;

//grab the notes and loop through them
var notes = document.querySelectorAll('.e-content');
for (var i = 0; i < notes.length; i++) {
    //get Youtube ID
    var ytid = notes[i].textContent.match(ytidregex);
    if (ytid !== null) {
        var yid = ytid[1];
        var yiframe = document.createElement('iframe');
        yiframe.classList.add('youtube');
        yiframe.setAttribute('src', '//www.youtube.com/embed/' + yid);
        yiframe.setAttribute('frameborder', 0);
        yiframe.setAttribute('allowfullscreen', 'true');
        notes[i].appendChild(yiframe);
    }
    //get Spotify ID
    var spotifyid = notes[i].textContent.match(spotifyregex);
    if (spotifyid !== null) {
        var sid = spotifyid[1].replace('/', ':');
        var siframe = document.createElement('iframe');
        siframe.classList.add('spotify');
        siframe.setAttribute('src', 'https://embed.spotify.com/?uri=spotify:' + sid);
        siframe.setAttribute('frameborder', 0);
        siframe.setAttribute('allowtransparency', 'true');
        notes[i].appendChild(siframe);
    }
    //now linkify everything
    var orig = notes[i].innerHTML;
    var linked = autolinker.link(orig);
    notes[i].innerHTML = linked;
}
