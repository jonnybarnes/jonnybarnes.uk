function getLocation(){navigator.geolocation.getCurrentPosition(function(e){addPlacesMap(e.coords.latitude,e.coords.longitude,e.coords.accuracy)})}function addPlacesMap(e,t,a){fetch("/places/near/"+e+"/"+t+"?u="+a,{credentials:"same-origin",method:"get"}).then(function(e){return e.json()}).then(function(a){if(1==a.error&&(alertify.reset(),alertify.error(a.error_description)),a.places.length>0){var n,d=[];for(n=0;n<a.places.length;++n){var l=parseLocation(a.places[n].location),r=a.places[n].name,c=a.places[n].uri;d.push([r,c,l[0],l[1]])}addMap(e,t,d)}else addMap(e,t)}).catch(function(e){console.error(e)})}function addMap(e,t,a){2==arguments.length&&(a=null);var n=document.querySelector(".note-ui"),d=document.createElement("div");d.classList.add("map"),n.appendChild(d),mapboxgl.accessToken="pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiY2l2cDhjYW04MDAwcjJ0cG1uZnhqcm82ayJ9.qA2zeVA-nsoMh9IFrd5KQw";var l=new mapboxgl.Map({container:d,style:"mapbox://styles/mapbox/streets-v9",center:[t,e],zoom:15});l.addControl(new mapboxgl.NavigationControl);var r=document.createElement("div");r.classList.add("marker");var c=document.createElement("div");c.classList.add("map-menu");var i=document.createElement("input");i.setAttribute("id","streets"),i.setAttribute("type","radio"),i.setAttribute("name","toggle"),i.setAttribute("value","streets"),i.setAttribute("checked","checked"),i.addEventListener("click",function(){l.setStyle("mapbox://styles/mapbox/streets-v9")});var o=document.createElement("label");o.setAttribute("for","streets"),o.appendChild(document.createTextNode("Streets"));var u=document.createElement("input");u.setAttribute("id","satellite"),u.setAttribute("type","radio"),u.setAttribute("name","toggle"),u.setAttribute("value","streets"),u.addEventListener("click",function(){l.setStyle("mapbox://styles/mapbox/satellite-v9")});var p=document.createElement("label");p.setAttribute("for","satellite"),p.appendChild(document.createTextNode("Satellite")),c.appendChild(i),c.appendChild(o),c.appendChild(u),c.appendChild(p),d.appendChild(c),new mapboxgl.Marker(r,{offset:[-10,-20]}).setLngLat([t,e]).addTo(l);var s=document.createElement("div"),m=document.createElement("select");m.setAttribute("name","location");var b=document.createElement("option");b.setAttribute("value","no-location"),b.appendChild(document.createTextNode("Don’t send location")),m.appendChild(b);var v=document.createElement("option");v.setAttribute("selected","selected"),v.setAttribute("id","option-coords"),v.setAttribute("value","geo:"+e+","+t),v.dataset.latitude=e,v.dataset.longitude=t,v.appendChild(document.createTextNode("Send co-ordinates")),m.appendChild(v),s.appendChild(m),n.insertBefore(s,d),null!==a&&(a.forEach(function(e){var t=document.createElement("option");t.setAttribute("value",e[1]);var a=document.createTextNode(e[0]);t.appendChild(a),t.dataset.latitude=e[2],t.dataset.longitude=e[3],m.appendChild(t);var n=document.createElement("div");n.classList.add("marker"),new mapboxgl.Marker(n,{offset:[-10,-20]}).setLngLat([e[3],e[2]]).addTo(l),n.addEventListener("click",function(){l.flyTo({center:[e[3],e[2]]}),selectPlace(e[1])})}),m.addEventListener("change",function(){if("no-location"!==m.value){var e=m[m.selectedIndex].dataset.latitude,t=m[m.selectedIndex].dataset.longitude;l.flyTo({center:[t,e]})}}));var h=document.createElement("button");h.setAttribute("type","button"),h.setAttribute("id","create-new-place"),h.appendChild(document.createTextNode("Create New Place?")),h.addEventListener("click",function(){var e=document.createElement("div"),t=document.createElement("label");t.setAttribute("for","place-name"),t.classList.add("place-label"),t.appendChild(document.createTextNode("Place Name:"));var a=document.createElement("input");a.setAttribute("placeholder","Name"),a.setAttribute("name","place-name"),a.setAttribute("id","place-name"),a.setAttribute("type","text"),e.appendChild(t),e.appendChild(a);var d=document.createElement("div"),r=document.createElement("label");r.setAttribute("for","place-description"),r.classList.add("place-label"),r.appendChild(document.createTextNode("Place Description:"));var c=document.createElement("input");c.setAttribute("placeholder","Description"),c.setAttribute("name","place-description"),c.setAttribute("id","place-description"),c.setAttribute("type","text"),d.appendChild(r),d.appendChild(c);var i=document.createElement("div"),o=document.createElement("label");o.setAttribute("for","place-latitude"),o.classList.add("place-label"),o.appendChild(document.createTextNode("Place Latitude:"));var u=document.createElement("input");u.setAttribute("name","place-latitude"),u.setAttribute("id","place-latitude"),u.setAttribute("type","text"),u.value=getLatitudeFromMapbox(l.getCenter()),i.appendChild(o),i.appendChild(u);var p=document.createElement("div"),s=document.createElement("label");s.setAttribute("for","place-longitude"),s.classList.add("place-label"),s.appendChild(document.createTextNode("Place Longitude:"));var b=document.createElement("input");b.setAttribute("name","place-longitude"),b.setAttribute("id","place-longitude"),b.setAttribute("type","text"),b.value=getLongitudeFromMapbox(l.getCenter()),p.appendChild(s),p.appendChild(b);var v=document.createElement("button");v.setAttribute("id","place-submit"),v.setAttribute("value","Submit New Place"),v.setAttribute("name","place-submit"),v.setAttribute("type","button"),v.appendChild(document.createTextNode("Submit New Place")),n.appendChild(e),n.appendChild(d),n.appendChild(i),n.appendChild(p),n.appendChild(v),v.addEventListener("click",function(){var e=new FormData;e.append("place-name",document.querySelector("#place-name").value),e.append("place-description",document.querySelector("#place-description").value),e.append("place-latitude",document.querySelector("#place-latitude").value),e.append("place-longitude",document.querySelector("#place-longitude").value),fetch("/places/new",{credentials:"same-origin",method:"post",body:e}).then(function(e){return e.json()}).then(function(e){if(1==e.error)throw new Error(e.error_description);for(var t=document.querySelectorAll(".place-label"),a=0;a<t.length;++a)n.removeChild(t[a].parentNode);n.removeChild(document.querySelector("#place-submit"));var d=document.querySelector("#create-new-place");d.parentNode.removeChild(d);var r=document.createElement("option");r.setAttribute("value",e.uri),r.appendChild(document.createTextNode(e.name)),r.dataset.latitude=e.latitude,r.dataset.longitude=e.longitude,m.appendChild(r);var c=document.createElement("div");c.classList.add("marker");new mapboxgl.Marker(c,{offset:[-10,-20]}).setLngLat([e.longitude,e.latitude]).addTo(l);l.flyTo({center:[e.longitude,e.latitude]}),c.addEventListener("click",function(){l.flyTo({center:[e.longitude,e.latitude]}),selectPlace(e.uri)}),selectPlace(e.uri)}).catch(function(e){alertify.reset(),alertify.error(e)})})}),s.appendChild(h)}function parseLocation(e){var t=/\((.*)\)/,a=t.exec(e),n=a[1].split(" ");return[n[1],n[0]]}function selectPlace(e){document.querySelector('select [value="'+e+'"]').selected=!0}function getLatitudeFromMapbox(e){var t=/\((.*)\)/.exec(e),a=t[1].split(" ");return a[1]}function getLongitudeFromMapbox(e){var t=/\((.*)\)/.exec(e),a=t[1].split(" ");return a[0].replace(",","")}if("geolocation"in navigator){var button=document.querySelector("#locate");button.addEventListener&&(button.disabled=!1,button.addEventListener("click",getLocation))}
//# sourceMappingURL=maps/newnote.js.map
