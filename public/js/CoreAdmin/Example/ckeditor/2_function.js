const resetEditor = () => {
  // console.log(MyCkEditor.ckEditor);
  if (MyCkEditor.ckEditor !== null) {
    MyCkEditor.ckEditor.setData("");
  }
};

const myHTMLEncode = (text) => {
  let encData = text
    .replaceAll('</', '##LT##')
    .replaceAll('<', '##L##')
    .replaceAll('>', '##K##');
  return encData;
};

const myHTMLDecode = (text) => {
  let encData = text
    .replaceAll('##LT##', '</')
    .replaceAll('##L##', '<')
    .replaceAll('##K##', '>');
  return encData;
};

const submitForm = () => {
  let param = {
    'text-editor': MyCkEditor.getEncryptedData(),
    'via': "Form Post"
  };
  post("/example/ckeditor", param, "post");
};

const submitAjax = () => {
  let formData = new FormData();
  formData.append('text-editor', MyCkEditor.getEncryptedData());
  formData.append('via', "AJAX Post");

  let csrf = getCSRFToken();
  let header = {};
  header[csrf.name] = csrf.val;

  if (AJAXOpt.xhr !== null) AJAXOpt.xhr.abort();
  AJAXOpt.xhr = callXHR("/example/ckeditor", "post", formData, header, AJAXOpt.onLoadStart, () => { }, () => { }, AJAXOpt.onLoad, AJAXOpt.onError, AJAXOpt.onTimeout, AJAXOpt.onAbort);
};

const submitFetch = () => {
  let formData = new FormData();
  formData.append('text-editor', MyCkEditor.getEncryptedData());
  formData.append('via', "Fetch Post");
  let csrf = getCSRFToken();
  fetchWithTimeout(
    "/example/ckeditor",
    {
      method: 'POST',
      redirect: 'error',
      headers: {
        Accept: 'application/json',
        [csrf.name] : csrf.val
      },
      body: formData
    },
    500
  )
  .then((response) => response.json())
  .then((json) => {
    console.log(json)
  })
  .catch((error) => {
    console.error(error.message)
  })
};