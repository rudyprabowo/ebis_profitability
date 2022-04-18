var LoginForm = {
  xhr: null,
  loadStart: function (e) {
    showLoader("on validate, please wait...");
  },
  load: function (e) {
    let response = LoginForm.xhr.response;
    let status = LoginForm.xhr.status;
    // console.log(status, response);
    LoginForm.xhr = null;
    if (status === 200) {
      let res = {};
      try {
        res = JSON.parse(response);
      } catch (e) {
        res.ret = false;
      }
      // console.log(res);
      if (res.ret) {
        updateLoader("Success to authenticate, redirecting ...");
        let redirect = SCRIPT.redirect_url;
        if(res.data.redirect!==undefined && res.data.redirect!==""){
          redirect = res.data.redirect;
        }
        // console.log(redirect);
        setTimeout(
          function () {
            window.location.replace(redirect);
          }, 1000);
      } else {
        if(res.msg==="MAXIMUM TRY"){
          showInfo("x", "danger", `Failed to authenticate<br>please try again after ${res.data.wait.minute} min ${res.data.wait.second} sec`);
        } else if (res.msg === "USER BLOCKED") {
          showInfo("x", "danger", "User is blocked, please contact adminstrator");
        }else {
          showInfo("x", "danger", "Failed to authenticate, please try again");
        }
        hideLoader();
      }
    } else if(status===401){
      updateLoader("Expire session, reloading page ...");
      setTimeout(
        function () {
          // window.location.reload();
          window.location.replace(SCRIPT.redirect_url);
        }, 1000);
    } else {
      showInfo("x", "danger", "Failed to authenticate, please try again");
      hideLoader();
    }
  },
  error: function (e) {
    showInfo("x", "danger", "Failed to authenticate, please try again");
    hideLoader();
    LoginForm.xhr = null;
  },
  timeout: function (e) {
    showInfo("x", "danger", "Failed to authenticate, please try again");
    hideLoader();
    LoginForm.xhr = null;
  },
  abort: function (e) {
    hideLoader();
    LoginForm.xhr = null;
  },
  authenticate: function (e) {
    let me = this;
    if (COMP.login_form !== null) {
      let input = COMP.login_form.querySelectorAll("input");
      // console.log(input,input.length);
      let meta = document.getElementsByTagName('meta');
      // console.log(meta);
      let data = new FormData();
      let header = {};

      for (let idx = 0; idx<input.length; idx++){
        // console.log(idx,input[idx]);
        if (input[idx].type === "checkbox") {
          data.append(input[idx].name, input[idx].checked);
        } else {
          data.append(input[idx].name, input[idx].value);
        }
      }

      for (let idx = 0; idx<meta.length; idx++){
        // console.log(idx,meta[idx].name,meta[idx].content);
        let name = meta[idx].name;
        let suffix = "-Csrf-Token";
        // console.log(name.length,suffix.length,(name.length-suffix.length), name.indexOf("-CSRF-Token"));
        if (name.indexOf("-Csrf-Token")===(name.length-suffix.length)) {
          let content = meta[idx].content;
          data.append(name, content);
          header[name] = content;
        }
      }

      if (LoginForm.xhr !== null) LoginForm.xhr.abort();
      LoginForm.xhr = callXHR(SCRIPT.auth_url, "POST", data, header, me.loadStart, function () { }, function () { }, me.load, me.error, me.timeout, me.abort);
    }
    e.preventDefault();
    e.stopPropagation();
    return false;
  }
};