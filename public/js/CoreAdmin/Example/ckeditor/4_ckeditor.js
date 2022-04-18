var MyCkEditor = {
  dom: null,
  ckObject: null,
  ckEditor: null,
  init: () => {
    let me = MyCkEditor;
    me.ckObject = ClassicEditor
    .create(document.querySelector('#editor'), {
      removePlugins: ['Title','Markdown'],
      toolbar: {
        items: [
          'bold',
          'italic',
          'underline',
          'strikethrough',
          'subscript',
          'superscript',
          'highlight',
          'fontBackgroundColor',
          'fontColor',
          'fontSize',
          'fontFamily',
          'link',
          '|',
          'outdent',
          'indent',
          'todoList',
          'numberedList',
          'bulletedList',
          '|',
          'alignment',
          'specialCharacters',
          'imageInsert',
          'imageUpload',
          'horizontalLine',
          'code',
          'codeBlock',
          'blockQuote',
          'insertTable',
          'mediaEmbed',
          '|',
          'findAndReplace',
          'undo',
          'redo',
          'heading'
        ]
      },
      language: 'en',
      image: {
        toolbar: [
          'imageTextAlternative',
          'imageStyle:inline',
          'imageStyle:block',
          'imageStyle:side',
          'linkImage'
        ]
      },
      table: {
        contentToolbar: [
          'tableColumn',
          'tableRow',
          'mergeTableCells',
          'tableCellProperties',
          'tableProperties'
        ]
      },
      title: {
        placeholder: " "
      },
      placeholder: " ",
      licenseKey: ''
    })
    .then(editor => {
      // console.log(Array.from( editor.ui.componentFactory.names() ));
      me.ckEditor = editor; 
      editor.model.document.on( 'change:data', (e, name, newval, oldval) => {
        // console.log(e, name, newval, oldval);
        // console.log(editor.getData());
        let data = editor.getData();

        showDom.innerText = data;
        showDom2.innerText = MyCkEditor.getEncryptedData();
      }); 
    })
    .catch( error => {
        console.error( error );
    });
  },
  getEncryptedData: () => {
    let data = MyCkEditor.ckEditor.getData();
    return myHTMLEncode(data);
  }
};