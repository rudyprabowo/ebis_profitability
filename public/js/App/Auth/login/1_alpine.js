document.addEventListener('alpine:initializing', () => {
  Alpine.store('state', {
    store_ready: false,
    dom_ready: false,
    wait: _wait
  });

  Alpine.store('loader', {
    main: true
  });

  Alpine.store('msg', {
    info: "",
    form: {
      username: '',
      password: '',
      captcha: ''
    }
  });

  Alpine.store('login_form', {
    value: {
      username: '',
      password: '',
      meta_csrf_name: '',
      meta_csrf_value: '',
      captcha_val: '',
      captcha_id: ''
    },
    readonly: {
      username: true,
      password: true,
      captcha: true
    },
    getAll: () => {
      const loginFormStore = Alpine.store('login_form');
      return {
        username: loginFormStore.value.username,
        password: loginFormStore.value.password,
        captcha: loginFormStore.value.captcha,
      };
    }
  });
});