var feature = {
  addEventListener : !!window.addEventListener,
  querySelectorAll : !!document.querySelectorAll,
};
if(feature.addEventListener && feature.querySelectorAll) {
  this.init();
}
function init() {
  var keys = getKeys();
  for(var i = 0; i < keys.length; i++) {
    if(store.get(keys[i])) {
      var formId = keys[i].split("~")[1];
      document.getElementById(formId).value = store.get(keys[i]);
    }
  }
}
var timerId = window.setInterval(function() {
  var saved = false;
  var inputs = document.querySelectorAll('input[type=text], textarea');
  for(var i = 0; i < inputs.length; i++) {
    var key = getFormElement(inputs[i]).id + '~' + inputs[i].id;
    if(store.get(key) !== inputs[i].value && inputs[i].value !== "") {
      store.set(key, inputs[i].value);
      saved = true;
    }
  }
  if(saved === true) {
    alertify.logPosition('top right');
    alertify.success('Auto saved text');
  }
}, 5000);
var forms = document.querySelectorAll('form');
for(var f = 0; f < forms.length; f++) {
  var form = forms[f];
  form.addEventListener('submit', function() {
    window.clearInterval(timerId);
    var formId = form.id;
    var storedKeys = store.keys();
    for(var i = 0; i < storedKeys.length; i++) {
      if(storedKeys[i].indexOf(formId) > -1) {
        store.remove(storedKeys[i]);
      }
    }
  });
}
function getKeys() {
  var keys = [];
  var formFields = document.querySelectorAll('input[type=text], textarea');
  for(var f = 0; f < formFields.length; f++) {
    var parent = getFormElement(formFields[f]);
    if(parent !== false) {
      var key = parent.id + '~' + formFields[f].id;
      keys.push(key);
    }
  }
  return keys;
}
function getFormElement(elem) {
  if(elem.nodeName.toLowerCase() !== 'body') {
    var parent = elem.parentNode;
    if(parent.nodeName.toLowerCase() === 'form') {
      return parent;
    } else {
      return getFormElement(parent);
    }
  } else {
    return false;
  }
}
