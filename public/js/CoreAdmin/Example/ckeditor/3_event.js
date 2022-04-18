document.addEventListener('DOMContentLoaded', e => {
  var showDom = document.getElementById('show-content');
  var showDom2 = document.getElementById('show-content2');

  MyCkEditor.dom = document.getElementById('editor');
  MyCkEditor.init();
});