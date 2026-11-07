/*
 * Apply a saved theme choice before first paint to avoid a flash of the system theme.
 * Loaded as a blocking external script (not inline) so it passes the site's CSP.
 */
try {
  const theme = localStorage.getItem('theme');
  if (theme === 'light' || theme === 'dark') {
    document.documentElement.style.setProperty('color-scheme', theme);
  }
} catch {
  /* storage unavailable, fall back to the system theme */
}
