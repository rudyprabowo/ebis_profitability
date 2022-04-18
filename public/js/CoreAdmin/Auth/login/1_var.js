var COMP = {
  loader: null,
  loader_el: null,
  loader_msg: null,
  info_el: null,
  info_modal: null,
  logo_content: null,
  login_content: null,
  login_form: null,
  initComponent: function () {
    COMP.logo_content = document.getElementById("logo-content");
    COMP.login_content = document.getElementById("login-content");
    COMP.login_form = document.getElementById("login-form");
    COMP.login_form.addEventListener('submit', function (event) {
      if (!COMP.login_form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      } else {
        LoginForm.authenticate(event);
      }
  
      COMP.login_form.classList.add('was-validated');
    }, false);
    
    COMP.loader_el = document.getElementById('loader-modal');
    COMP.loader = new bootstrap.Modal(COMP.loader_el, {
      backdrop: 'static',
      keyboard: false
    });
  
    COMP.loader_el.addEventListener('show.bs.loader', function (event) {
      COMP.loader_msg.textContent = "processing ...";
      COMP.loader_msg.textContent = msg;
    });
  
    COMP.loader_msg = document.getElementById('loader-msg');
  }
};