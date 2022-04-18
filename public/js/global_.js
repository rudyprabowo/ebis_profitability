const fetchWithTimeout = (uri, options = {}, time = 5000) => {
  // Lets set up our `AbortController`, and create a request options object
  // that includes the controller's `signal` to pass to `fetch`.
  const controller = new AbortController();
  const config = {
    ...options,
    signal: controller.signal
  };
  // Set a timeout limit for the request using `setTimeout`. If the body
  // of this timeout is reached before the request is completed, it will
  // be cancelled.
  const timeout = setTimeout(() => {
    controller.abort();
  }, time);

  return fetch(uri, config)
    .then((response) => {
      // Because _any_ response is considered a success to `fetch`, we
      // need to manually check that the response is in the 200 range.
      // This is typically how I handle that.
      // console.log(response);
      if (!response.ok) {
        throw new Error(response.status+': '+response.statusText);
        // throw new Error("error");
      }
      // response.ok = true;
      // console.log(response);
      return response
    })
    .catch((error) => {
      // When we abort our `fetch`, the controller conveniently throws
      // a named error, allowing us to handle them separately from
      // other errors.
      if (error.name === 'AbortError') {
        throw new Error('Response timed out')
      }
      throw new Error(error.message)
    });
};
// fetchWithTimeout(
//     'https://httpstat.us/200?sleep=1000', {
//       headers: {
//         Accept: 'application/json'
//       }
//     },
//     500
//   )
//   .then((response) => response.json())
//   .then((json) => {
//     console.log(`This will never log out: ${json}`)
//   })
//   .catch((error) => {
//     console.error(error.message)
// })

const getCSRFToken = function () {
  let ret = {
    name: null,
    val: null
  };
  let meta = document.getElementsByTagName('meta');
  for (let idx = 0; idx<meta.length; idx++){
    let name = meta[idx].name;
    let suffix = "-Csrf-Token";
    if (name.indexOf("-Csrf-Token")===(name.length-suffix.length)) {
      ret.name = name;
      ret.val = meta[idx].content;
      break;
    }
  }
  return ret;
}

const returnMethods = (obj = {}) => {
  const members = Object.getOwnPropertyNames(obj);
  const methods = members.filter(el => {
     return typeof obj[el] === 'function';
  })
  return methods;
};

/**
 * sends a request to the specified url from a form. this will change the window location.
 * @param {string} path the path to send the post request to
 * @param {object} params the parameters to add to the url
 * @param {string} [method=post] the method to use on the form
 */
function post(path, params, method='post') {

  // The rest of this code assumes you are not using a library.
  // It can be made less verbose if you use one.
  const form = document.createElement('form');
  form.method = method;
  form.action = path;

  for (const key in params) {
    if (params.hasOwnProperty(key)) {
      const hiddenField = document.createElement('input');
      hiddenField.type = 'hidden';
      hiddenField.name = key;
      hiddenField.value = params[key];

      form.appendChild(hiddenField);
    }
  }

  document.body.appendChild(form);
  form.submit();
}

function callXHR(url, method = "POST", data, header = {}, onLoadStart = () => { }, onUploadProgress = () => { }, onProgress = () => { }, onLoad = () => { }, onError = () => { }, onTimeout = () => { }, onAbort = () => { }) {
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

const extendObj = (superClass, subClass) => {
  const handler = {
    construct: function (target, args) {
      const newObject = {}
      // we populate the new object with the arguments from
      superClass.call(newObject, ...args);
      subClass.call(newObject, ...args);
      return newObject;
    },
  }
  return new Proxy(subClass, handler);
}

function cloneObj(obj) {
  if (null == obj || "object" != typeof obj) return obj;
  var copy = obj.constructor();
  for (var attr in obj) {
    if (obj.hasOwnProperty(attr)) copy[attr] = obj[attr];
  }
  return copy;
}

function byteConvert(number) {
  if(number < 1024) {
    return number + 'bytes';
  } else if(number >= 1024 && number < 1048576) {
    return (number/1024).toFixed(1) + 'KB';
  } else if(number >= 1048576 && number < 1073741824) {
    return (number/1048576).toFixed(1) + 'MB';
  } else if(number >= 1073741824) {
    return (number/1073741824).toFixed(1) + 'GB';
  }
}
var objectToFormData = function (obj, form, namespace) {

  var fd = form || new FormData();
  var formKey;

  for (var property in obj) {
    if (obj.hasOwnProperty(property)) {

      if (namespace) {
        formKey = namespace + '[' + property + ']';
      } else {
        formKey = property;
      }

      // if the property is an object, but not a File,
      // use recursivity.
      if (typeof obj[property] === 'object' && !(obj[property] instanceof File)) {

        objectToFormData(obj[property], fd, formKey);

      } else {

        // if it's a string or a File object
        fd.append(formKey, obj[property]);
      }

    }
  }

  return fd;

};

function isJson(item) {
  item = typeof item !== "string"
    ? JSON.stringify(item)
    : item;

  try {
    item = JSON.parse(item);
  } catch (e) {
    return false;
  }

  if (typeof item === "object" && item !== null) {
    return true;
  }

  return false;
}

var hidden, visibilityChange;
if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
  hidden = "hidden";
  visibilityChange = "visibilitychange";
} else if (typeof document.msHidden !== "undefined") {
  hidden = "msHidden";
  visibilityChange = "msvisibilitychange";
} else if (typeof document.webkitHidden !== "undefined") {
  hidden = "webkitHidden";
  visibilityChange = "webkitvisibilitychange";
}