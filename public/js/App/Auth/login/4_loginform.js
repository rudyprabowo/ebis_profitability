const objLoginForm = {
  resetVal: () => {
    const loginFormStore = Alpine.store('login_form');
    loginFormStore.value.username = "";
    loginFormStore.value.password = "";
    loginFormStore.value.captcha = "";
  },
  setMetaValue: () => {
    const csrf = getCSRFToken();
    // console.log(csrf);
    const loginFormStore = Alpine.store('login_form');
    loginFormStore.value.meta_csrf_name = csrf.name;
    loginFormStore.value.meta_csrf_value = csrf.val;
  },
  addEvent: () => {
    document.querySelector('form').addEventListener('submit', (e) => {
      // e.preventDefault();
      const loaderStore = Alpine.store('loader');
      loaderStore.main = true;
      // console.log(e);
      if(objLoginForm.validate()===true){
        // e.preventDefault();
        return true;
      } else {
        loaderStore.main = false;
        e.preventDefault();
        return false;
      }
    });
  },
  form_constraint: {
    username: {
      presence: { allowEmpty: false }, type: "string",
      length: { minimum: 5, maximum: 15, tooLong: "input is not valid", tooShort: "input is not valid" },
      format: {
        pattern: "[a-z0-9_]+",
        flags: "i",
        message: "input is not valid"
      }
    },
    password: {
      presence: { allowEmpty: false }, type: "string",
      length: { minimum: 5, maximum: 300, tooLong: "input is not valid", tooShort: "input is not valid" },
    },
    captcha: {
      presence: { allowEmpty: false }, type: "string"
    }
  },
  showMsg: (msg) => {
    const msgStore = Alpine.store('msg');
    _.forIn(msg, function (value, key) {
      msgStore.form[key] = value[0];
    });
    console.table(msgStore.form);
  },
  hideMsg: () => {
    const msgStore = Alpine.store('msg');
    msgStore.form.username = '';
    msgStore.form.password = '';
    msgStore.form.captcha = '';
  },
  validate: () => {
    objLoginForm.hideMsg();
    const loginFormStore = Alpine.store('login_form');
    let formData = loginFormStore.getAll();
    // console.log(formData, objLoginForm.form_constraint); return false;
    let ret = validate(formData, objLoginForm.form_constraint);
    // console.log(ret);
    // ret = false;
    if (ret === undefined) {
      return true;
    } else {
      objLoginForm.showMsg(ret);
      return false;
    }
  }
};