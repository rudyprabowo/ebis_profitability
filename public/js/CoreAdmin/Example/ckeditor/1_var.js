var showDom;
var showDom2;

var AJAXOpt = {
  xhr: null,
  onLoadStart: function (e) {
    console.log("Starting AJAX request");
  },
  onLoad: (e) => {
    console.log("AJAX Loaded");
    console.log(e);
    if (status === 200) {
      console.log("Request Success");
      //IF RETURN JSON
      let res = {};
      try {
        res = JSON.parse(response);
      } catch (e) {
        res.ret = false;
      }
      console.log(res);
    } else if (status === 401) {
      console.log("Expire Session, reloading page ...");
      setTimeout(
      function () {
        // window.location.reload();
        window.location.replace(SCRIPT.redirect_url);
      }, 1000);
    } else {
      console.log("Invalid Request");
    }
  },
  onError: function (e) {
    console.log("AJAX Error");
    console.log(e);
    AJAXOpt.xhr = null;
  },
  onTimeout: function (e) {
    console.log("AJAX Timeout");
    console.log(e);
    AJAXOpt.xhr = null;
  },
  onAbort: function (e) {
    console.log("AJAX Aborted");
    console.log(e);
    AJAXOpt.xhr = null;
  },
};
