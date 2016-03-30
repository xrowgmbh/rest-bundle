(function(meintest) {
  document.addEventListener('DOMContentLoaded', function() {
    ng.platform.browser.bootstrap(meintest.AppComponent);
  });
})(window.meintest || (window.meintest = {}));