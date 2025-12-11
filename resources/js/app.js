import { Auth } from './auth.js';
import { ThemeSelector } from './theme-selector.js';

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

let themeSelector = new ThemeSelector();
themeSelector.setupEventListeners();
