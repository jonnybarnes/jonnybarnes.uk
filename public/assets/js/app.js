import { Auth } from './auth.js';

let auth = new Auth();

document.querySelectorAll('.add-passkey').forEach((el) => {
  el.addEventListener('click', () => {
    auth.register();
  });
});

document.querySelectorAll('.login-passkey').forEach((el) => {
  el.addEventListener('click', () => {
    auth.login();
  });
});
