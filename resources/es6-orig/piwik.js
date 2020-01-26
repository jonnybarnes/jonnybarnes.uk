/* global process */

// Piwik in its own js file to allow usage with a CSP policy
var idSite = process.env.PIWIK_ID;
var piwikTrackingApiUrl = process.env.PIWIK_URL;

var _paq = _paq || [];
// tracker methods like "setCustomDimension" should be called before "trackPageView"
_paq.push(['setTrackerUrl', piwikTrackingApiUrl]);
_paq.push(['setSiteId', idSite]);
_paq.push(['trackPageView']);
_paq.push(['enableLinkTracking']);
