/*** namespace hier meintest ***/
/*** ng.core => global Angular core namespace ***/
/*** selector specifies a simple CSS selector for a host HTML element named my-app ***/
(function(meintest) {
    meintest.AppComponent =
    ng.core.Component({
      selector: 'my-app',
      template: '<h1>My First Angular 2 App</h1>'
    })
    .Class({
      constructor: function() {}
    });
})(window.meintest || (window.meintest = {}));