!function(modules){var installedModules={};function __webpack_require__(moduleId){if(installedModules[moduleId])return installedModules[moduleId].exports;var module=installedModules[moduleId]={i:moduleId,l:!1,exports:{}};return modules[moduleId].call(module.exports,module,module.exports,__webpack_require__),module.l=!0,module.exports}__webpack_require__.m=modules,__webpack_require__.c=installedModules,__webpack_require__.d=function(exports,name,getter){__webpack_require__.o(exports,name)||Object.defineProperty(exports,name,{configurable:!1,enumerable:!0,get:getter})},__webpack_require__.n=function(module){var getter=module&&module.__esModule?function(){return module.default}:function(){return module};return __webpack_require__.d(getter,"a",getter),getter},__webpack_require__.o=function(object,property){return Object.prototype.hasOwnProperty.call(object,property)},__webpack_require__.p="",__webpack_require__(__webpack_require__.s=6)}({6:function(module,exports,__webpack_require__){"use strict";var youtubeRegex=/watch\?v=([A-Za-z0-9\-_]+)\b/,spotifyRegex=/https:\/\/play\.spotify\.com\/(.*)\b/,notes=document.querySelectorAll(".e-content"),_iteratorNormalCompletion=!0,_didIteratorError=!1,_iteratorError=void 0;try{for(var _step,_iterator=notes[Symbol.iterator]();!(_iteratorNormalCompletion=(_step=_iterator.next()).done);_iteratorNormalCompletion=!0){var note=_step.value,ytid=note.textContent.match(youtubeRegex);if(ytid){var ytcontainer=document.createElement("div");ytcontainer.classList.add("container");var ytiframe=document.createElement("iframe");ytiframe.classList.add("youtube"),ytiframe.setAttribute("src","https://www.youtube.com/embed/"+ytid[1]),ytiframe.setAttribute("frameborder",0),ytiframe.setAttribute("allowfullscreen","true"),ytcontainer.appendChild(ytiframe),note.appendChild(ytcontainer)}var spotifyid=note.textContent.match(spotifyRegex);if(spotifyid){var sid=spotifyid[1].replace("/",":"),siframe=document.createElement("iframe");siframe.classList.add("spotify"),siframe.setAttribute("src","https://embed.spotify.com/?uri=spotify:"+sid),siframe.setAttribute("frameborder",0),siframe.setAttribute("allowtransparency","true"),note.appendChild(siframe)}}}catch(err){_didIteratorError=!0,_iteratorError=err}finally{try{!_iteratorNormalCompletion&&_iterator.return&&_iterator.return()}finally{if(_didIteratorError)throw _iteratorError}}}});
//# sourceMappingURL=links.js.map