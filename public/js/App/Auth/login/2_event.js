document.addEventListener('DOMContentLoaded', function(event) {
  const stateStore = Alpine.store('state');
  // console.log(stateStore);
  stateStore.dom_ready = true;
  if (stateStore.store_ready) {
    objLoginForm.resetVal();
    objLoginForm.setMetaValue();
    objLoginForm.addEvent();

    if (_msg != '') {
      const msgStore = Alpine.store('msg');
      msgStore.info = _msg;
    }
    loader.hideMainLoader();
  }
})

document.addEventListener('alpine:initializing', () => {
  Alpine.directive('html', (el, { expression }, { evaluateLater, effect }) => {
    let getHtml = evaluateLater(expression)

    effect(() => {
      getHtml(html => {
        el.innerHTML = html
      })
    })
  });
  
  const stateStore = Alpine.store('state');
  stateStore.store_ready = true;
  if (stateStore.dom_ready) {
    objLoginForm.resetVal();
    objLoginForm.setMetaValue();
    objLoginForm.addEvent();
    
    if (_msg != '') {
      const msgStore = Alpine.store('msg');
      msgStore.info = _msg;
    }
    loader.hideMainLoader();
  }
})