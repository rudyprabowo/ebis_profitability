function showForm() {
  // console.log("show");
  COMP.logo_content.classList.remove('animate__animated', 'animate__flipInY', 'animate__slow');
  COMP.logo_content.classList.add('animate__animated', 'animate__flipOutY', 'animate__slow');
  COMP.logo_content.addEventListener('animationend', function logo_animate_end() {
    COMP.logo_content.classList.add('d-none');
    COMP.logo_content.removeEventListener('animationend', logo_animate_end);
    COMP.login_content.classList.remove('d-none', 'animate__animated', 'animate__flipOutY', 'animate__slow');
    COMP.login_content.classList.add('animate__animated', 'animate__flipInY', 'animate__slow');
  });
}

function hideForm() {
  // console.log("hide");
  COMP.login_content.classList.remove('animate__animated', 'animate__flipInY', 'animate__slow');
  COMP.login_content.classList.add('animate__animated', 'animate__flipOutY', 'animate__slow');
  COMP.login_content.addEventListener('animationend', function login_content_end() {
    COMP.login_content.classList.add('d-none');
    COMP.login_content.removeEventListener('animationend', login_content_end);
    COMP.logo_content.classList.remove('d-none', 'animate__animated', 'animate__flipOutY', 'animate__slow');
    COMP.logo_content.classList.add('animate__animated', 'animate__flipInY', 'animate__slow');
  });
}

function showLoader(msg) {
  // console.log(component);
  updateLoader(msg);
  COMP.loader.show();
}

function updateLoader(msg) {
  COMP.loader_msg.innerHTML = "processing ...";
  COMP.loader_msg.innerHTML = msg;
}

function hideLoader() {
  if (COMP.loader !== null) {
    COMP.loader.hide();
  }
}

function showInfo(icon,color,msg) {
  COMP.info_el = document.getElementById('info-modal');
  COMP.info_modal = new bootstrap.Modal(COMP.info_el);
  COMP.info_el.addEventListener('show.bs.modal', function (event) {
    COMP.info_el.classList.remove("animate__animated","animate__flipInY");
    COMP.info_el.classList.add("animate__animated","animate__flipInY");

    if (color !== "danger" && color !== "success" && color !== "primary") color = "dark";
    if (icon !== "check" && icon !== "x") icon = "info";

    // Update the modal's content.
    let modal_icon = document.getElementById('info-icon');
    let modal_msg = document.getElementById('info-msg');
    let modal_btn = document.getElementById('info-btn');

    modal_icon.classList.remove("bi-check-circle", "bi-x-circle", "bi-info-circle");
    modal_icon.classList.add("bi-"+icon+"-circle");
    modal_icon.classList.remove("text-danger", "text-success", "text-primary", "text-dark");
    modal_icon.classList.add("text-" + color);

    modal_msg.innerHTML = "";
    modal_msg.innerHTML = msg;
    modal_msg.classList.remove("text-danger", "text-success", "text-primary", "text-dark");
    modal_msg.classList.add("text-" + color);

    modal_btn.classList.remove("btn-danger", "btn-success", "btn-primary", "btn-dark");
    modal_btn.classList.add("btn-" + color);
  })
  // console.log(component);
  COMP.info_modal.show();
}

function updateInfo(icon,color,msg) {
  if (color !== "danger" && color !== "success" && color !== "primary") color = "dark";
  if (icon !== "check" && icon !== "x") icon = "info";

  // Update the modal's content.
  let modal_icon = document.getElementById('info-icon');
  let modal_msg = document.getElementById('info-msg');
  let modal_btn = document.getElementById('info-btn');

  modal_icon.classList.remove("bi-check-circle", "bi-x-circle", "bi-info-circle");
  modal_icon.classList.add("bi-"+icon+"-circle");
  modal_icon.classList.remove("text-danger", "text-success", "text-primary", "text-dark");
  modal_icon.classList.add("text-" + color);

  modal_msg.innerHTML = "";
  modal_msg.innerHTML = msg;
  modal_msg.classList.remove("text-danger", "text-success", "text-primary", "text-dark");
  modal_msg.classList.add("text-" + color);

  modal_btn.classList.remove("btn-danger", "btn-success", "btn-primary", "btn-dark");
  modal_btn.classList.add("btn-" + color);
}

function callXHR(url, method = "POST", data, header = {}, onLoadStart, onUploadProgress, onProgress, onLoad, onError, onTimeout, onAbort) {
  let request = new XMLHttpRequest();
  request.open(method, url);
  request.addEventListener('loadstart', onLoadStart);
  request.upload.addEventListener('progress', onUploadProgress);
  request.addEventListener('progress', onProgress);
  request.addEventListener('load', onLoad);
  request.addEventListener('error', onError);
  request.addEventListener('timeout', onTimeout);
  request.addEventListener('abort', onAbort);
  request.setRequestHeader("X-Requested-With", "XMLHttpRequest");

  for (let prop in header) {
    request.setRequestHeader(prop, header[prop]);
  }
  // console.log(header);
  request.send(data);

  return request;
}