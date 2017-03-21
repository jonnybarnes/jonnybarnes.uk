//persist-form.js

import webStorage from 'webStorage';
import alertify from 'alertify.js';

const loadData = () => {
    let replyTo = document.querySelector('#in-reply-to');
    replyTo.value = webStorage.getItem('replyTo');
    let content = document.querySelector('#content');
    content.value = webStorage.getItem('content');
};

const saveData = () => {
    let replyTo = document.querySelector('#in-reply-to');
    let content = document.querySelector('#content');
    webStorage.setItem('replyTo', replyTo.value);
    webStorage.setItem('content', content.value);
    alertify.success('Auto-saved data');
};

const clearData = () => {
    webStorage.removeItem('replyTo');
    webStorage.removeItem('content');
};

export default function persistFormData()
{
    let form = document.querySelector('form[name="micropub"]');
    form.addEventListener('change', saveData);
    form.addEventListener('submit', clearData);
    loadData();
}
