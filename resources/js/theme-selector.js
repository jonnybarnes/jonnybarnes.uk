class ThemeSelector {
  constructor() {
    this.widget = document.querySelector('#theme-selector');
  }

  setupEventListeners() {
    this.widget.querySelectorAll('#theme-selector-dropdown input').forEach((radioInput) => radioInput.addEventListener('input', (e) => {
      let theme = e.target.value;

      // Update current icon
      this.widget.querySelectorAll('.toggle svg').forEach((svg) => {
        if (svg.classList.contains(theme)) {
          svg.style.display = '';
        } else {
          svg.style.display = 'none';
        }
      });

      // Set the theme
      let selectedTheme;
      switch (theme) {
        case 'dark':
        case 'light':
          selectedTheme = theme;
          break;
        default:
          selectedTheme = 'light dark';
      }

      const systemTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
      const html = document.querySelector('html');
      let currentTheme = html.style.getPropertyValue('color-scheme');
      if (currentTheme === '') {
        currentTheme = 'light dark';
      }

      /* do we need to transition */
      let doTransition = false;

      if (selectedTheme !== currentTheme) {
        if (selectedTheme === 'light dark') {
          doTransition = currentTheme !== systemTheme;
        } else if (currentTheme === 'light dark') {
          doTransition = selectedTheme !== systemTheme;
        } else {
          doTransition = true;
        }
      }

      html.style.viewTransitionName = 'changing-theme';
      if (doTransition && document.startViewTransition) {
        document.startViewTransition(() => {
          // Close the popover
          document.querySelector('#theme-selector-dropdown').togglePopover();

          // Set the colour theme
          html.style.setProperty('color-scheme', selectedTheme);
        });
      } else {
        // Close the popover
        document.querySelector('#theme-selector-dropdown').togglePopover();

        // Set the colour theme
        html.style.setProperty('color-scheme', selectedTheme);
      }
    }));
  }
}

export { ThemeSelector };
