//the autlinker object
var autolinker = new Autolinker();

//the youtube regex
var ytidregex = /watch\?v=([A-Za-z0-9\-_]+)/;

//grab the notes and loop through them
var notes = document.querySelectorAll('.e-content');
for(var i = 0; i < notes.length; i++) {
  //get Youtube ID
  var ytid = notes[i].textContent.match(ytidregex);
  if(ytid !== null) {
  	var id = ytid[1];
  	var iframe = document.createElement('iframe');
  	iframe.classList.add('youtube');
  	iframe.setAttribute('src', '//www.youtube.com/embed/' + id);
  	iframe.setAttribute('frameborder', 0);
  	iframe.setAttribute('allowfullscreen', 'true');
  	notes[i].appendChild(iframe);
  }
  //now linkify everything
  var orig = notes[i].innerHTML;
  var linked = autolinker.link(orig);
  notes[i].innerHTML = linked;
}