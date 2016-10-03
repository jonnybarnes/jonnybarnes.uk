function getLocation(){navigator.geolocation.getCurrentPosition(function(e){addPlacesMap(e.coords.latitude,e.coords.longitude,e.coords.accuracy)})}function addPlacesMap(e,t,a){fetch("/places/near/"+e+"/"+t+"?u="+a,{credentials:"same-origin",method:"get"}).then(function(e){return e.json()}).then(function(a){if(1==a.error&&(alertify.reset(),alertify.error(a.error_description)),a.places.length>0){var n,r=[];for(n=0;n<a.places.length;++n){var o=parseLocation(a.places[n].location),l=a.places[n].name,c=a.places[n].slug;r.push([l,c,o[0],o[1]])}addMap(e,t,r)}else addMap(e,t)}).catch(function(e){console.error(e)})}function addMap(e,t,a){2==arguments.length&&(a=null);var n=button.parentNode,r=document.createElement("div");r.setAttribute("id","map"),n.appendChild(r),L.mapbox.accessToken="pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiVlpndW1EYyJ9.aP9fxAqLKh7lj0LpFh5k1w";var o=L.mapbox.map("map","jonnybarnes.gnoihnim").setView([e,t],15).addLayer(L.mapbox.tileLayer("jonnybarnes.gnoihnim",{detectRetina:!0})),l=L.marker([e,t],{draggable:!0}).addTo(o);l.on("dragend",function(){var e=document.querySelector("#place-latitude");null!==e&&(e.value=getLatitudeFromMapboxMarker(l.getLatLng()));var t=document.querySelector("#place-longitude");null!==t&&(t.value=getLongitudeFromMapboxMarker(l.getLatLng()))});var c=document.createElement("select");c.setAttribute("name","location");var i=document.createElement("option");i.setAttribute("selected","selected"),i.setAttribute("value","no-location");var d=document.createTextNode("Select no location");i.appendChild(d),c.appendChild(i),n.insertBefore(c,r),null!==a&&(a.forEach(function(e){var t=document.createElement("option");t.setAttribute("value",e[1]);var a=document.createTextNode(e[0]);t.appendChild(a),t.dataset.latitude=e[2],t.dataset.longitude=e[3],c.appendChild(t);var n=L.marker([e[2],e[3]],{icon:L.mapbox.marker.icon({"marker-size":"large","marker-symbol":"building","marker-color":"#fa0"})}).addTo(o),r="Name: "+e[0];n.bindPopup(r,{closeButton:!0}),n.on("click",function(){o.panTo([e[2],e[3]]),selectPlace(e[1])})}),c.addEventListener("change",function(){if("no-location"!==c.value){var e=c[c.selectedIndex].dataset.latitude,t=c[c.selectedIndex].dataset.longitude;o.panTo([e,t])}}));var u=document.createElement("button");u.setAttribute("type","button"),u.setAttribute("id","create-new-place"),u.appendChild(document.createTextNode("Create New Place?")),u.addEventListener("click",function(){var e=document.createElement("label");e.setAttribute("for","place-name"),e.classList.add("place-label"),e.appendChild(document.createTextNode("Place Name:"));var t=document.createElement("input");t.setAttribute("placeholder","Name"),t.setAttribute("name","place-name"),t.setAttribute("id","place-name"),t.setAttribute("type","text");var a=document.createElement("label");a.setAttribute("for","place-description"),a.classList.add("place-label"),a.appendChild(document.createTextNode("Place Description:"));var r=document.createElement("input");r.setAttribute("placeholder","Description"),r.setAttribute("name","place-description"),r.setAttribute("id","place-description"),r.setAttribute("type","text");var i=document.createElement("label");i.setAttribute("for","place-latitude"),i.classList.add("place-label"),i.appendChild(document.createTextNode("Place Latitude:"));var d=document.createElement("input");d.setAttribute("name","place-latitude"),d.setAttribute("id","place-latitude"),d.setAttribute("type","text"),d.value=getLatitudeFromMapboxMarker(l.getLatLng());var u=document.createElement("label");u.setAttribute("for","place-longitude"),u.classList.add("place-label"),u.appendChild(document.createTextNode("Place Longitude:"));var p=document.createElement("input");p.setAttribute("name","place-longitude"),p.setAttribute("id","place-longitude"),p.setAttribute("type","text"),p.value=getLongitudeFromMapboxMarker(l.getLatLng());var s=document.createElement("button");s.setAttribute("id","place-submit"),s.setAttribute("value","Submit New Place"),s.setAttribute("name","place-submit"),s.setAttribute("type","button"),s.appendChild(document.createTextNode("Submit New Place")),n.appendChild(e),n.appendChild(t),n.appendChild(a),n.appendChild(r),n.appendChild(i),n.appendChild(d),n.appendChild(u),n.appendChild(p),n.appendChild(s),s.addEventListener("click",function(){var e=new FormData;e.append("place-name",document.querySelector("#place-name").value),e.append("place-description",document.querySelector("#place-description").value),e.append("place-latitude",document.querySelector("#place-latitude").value),e.append("place-longitude",document.querySelector("#place-longitude").value),fetch("/places/new",{credentials:"same-origin",method:"post",body:e}).then(function(e){return e.json()}).then(function(e){if(1==e.error)throw new Error(e.error_description);var t=e.split("/"),a=t.pop();n.removeChild(document.querySelector("#place-name")),n.removeChild(document.querySelector("#place-description")),n.removeChild(document.querySelector("#place-latitude")),n.removeChild(document.querySelector("#place-longitude"));for(var r=document.querySelectorAll(".place-label"),i=0;i<r.length;++i)n.removeChild(r[i]);n.removeChild(document.querySelector("#place-submit")),n.removeChild(document.querySelector("#create-new-place")),o.removeLayer(l);var d=document.createElement("option");d.setAttribute("value",a),d.appendChild(document.createTextNode(e.name)),d.dataset.latitude=e.latitude,d.dataset.longitude=e.longitude,c.appendChild(d);var u=L.marker([e.latitude,e.longitude],{icon:L.mapbox.marker.icon({"marker-size":"large","marker-symbol":"building","marker-color":"#fa0"})}).addTo(o),p="Name: "+e.name;u.bindPopup(p,{closeButton:!0}),u.on("click",function(){o.panTo([e.latitude,e.longitude]),selectPlace(a)}),selectPlace(a)}).catch(function(e){alertify.reset(),alertify.error(e)})})}),n.insertBefore(u,r)}function parseLocation(e){var t=/\((.*)\)/,a=t.exec(e),n=a[1].split(" ");return[n[1],n[0]]}function selectPlace(e){document.querySelector("select [value="+e+"]").selected=!0}function getLatitudeFromMapboxMarker(e){var t=/\((.*)\)/.exec(e),a=t[1].split(" ");return a[0].replace(",","")}function getLongitudeFromMapboxMarker(e){var t=/\((.*)\)/.exec(e),a=t[1].split(" ");return a[1]}if("geolocation"in navigator){var button=document.querySelector("#locate");button.addEventListener&&(button.disabled=!1,button.addEventListener("click",getLocation))}
//# sourceMappingURL=maps/newnote.js.map
