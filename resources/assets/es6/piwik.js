// Piwik in its own js file to allow usage with a CSP policy

var _paq = _paq || [];
// tracker methods like "setCustomDimension" should be called before "trackPageView"
_paq.push(['trackPageView']);
_paq.push(['enableLinkTracking']);
_paq.push(['setTrackerUrl', 'https://analytics.jmb.lv/piwik.php']);
_paq.push(['setSiteId', '1']);
