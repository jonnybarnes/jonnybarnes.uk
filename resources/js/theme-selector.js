class ThemeSelector {
  constructor() {
    this.widget = document.querySelector('#theme-selector');
    this.btnLight = this.widget.querySelector('#btn-light');
    this.btnDark = this.widget.querySelector('#btn-dark');
    this.status = this.widget.querySelector('#theme-status');
    this.currentTheme = ThemeSelector.storedTheme();
    this.updateButtons();
  }

  static storedTheme() {
    try {
      const theme = localStorage.getItem('theme');
      return (theme === 'light' || theme === 'dark') ? theme : 'system';
    } catch {
      return 'system';
    }
  }

  static storeTheme(theme) {
    try {
      if (theme === 'system') {
        localStorage.removeItem('theme');
      } else {
        localStorage.setItem('theme', theme);
      }
    } catch {
      /* storage unavailable, the choice just won't persist */
    }
  }

  updateButtons() {
    this.btnLight.setAttribute('aria-pressed', String(this.currentTheme === 'light'));
    this.btnDark.setAttribute('aria-pressed', String(this.currentTheme === 'dark'));
  }

  setupEventListeners() {
    this.btnLight.addEventListener('click', () => {
      this.applyTheme(this.currentTheme === 'light' ? 'system' : 'light');
    });

    this.btnDark.addEventListener('click', () => {
      this.applyTheme(this.currentTheme === 'dark' ? 'system' : 'dark');
    });
  }

  applyTheme(theme) {
    const selectedTheme = (theme === 'light' || theme === 'dark') ? theme : 'light dark';

    const systemTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    const html = document.documentElement;
    let currentColorScheme = html.style.getPropertyValue('color-scheme');
    if (currentColorScheme === '') {
      currentColorScheme = 'light dark';
    }

    /* do we need to transition */
    let doTransition = false;

    if (selectedTheme !== currentColorScheme) {
      if (selectedTheme === 'light dark') {
        doTransition = currentColorScheme !== systemTheme;
      } else if (currentColorScheme === 'light dark') {
        doTransition = selectedTheme !== systemTheme;
      } else {
        doTransition = true;
      }
    }

    const applyChange = () => {
      this.currentTheme = theme;
      html.style.setProperty('color-scheme', selectedTheme);
      ThemeSelector.storeTheme(theme);

      this.updateButtons();
      this.status.textContent = theme === 'system' ? 'Theme set to system default' : '';
    };

    html.style.viewTransitionName = 'changing-theme';
    if (doTransition && document.startViewTransition) {
      document.startViewTransition(applyChange);
    } else {
      applyChange();
    }
  }
}

export { ThemeSelector };
