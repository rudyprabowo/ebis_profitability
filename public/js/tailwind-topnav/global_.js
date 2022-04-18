document.addEventListener('alpine:init', () => {
  Alpine.store('loader', {
    main: false,
    msg: "processing....",
    show(msg) {
      this.msg = msg;
      this.main = true;
    },
    hide() {
      this.main = false;
      this.msg = "processing....";
    }
  });

  Alpine.store('confirm_modal', {
    main: false,
    type: "remove",
    title: 'THIS IS TITLE',
    main_content: "this is primary content",
    second_content: "this is secondary content",
    btn_text: "Submit",
    submitHandler: null,
    show(title, main_content, second_content, type, btn_text, callback) {
      this.title = title;
      this.main_content = main_content;
      this.second_content = second_content;
      this.type = type;
      this.btn_text = btn_text;
      if (callback !== null) {
        this.submitHandler = callback;
      }
      this.main = true;
    },
    hide(callback, param, wait = false) {
      let onwait = false;
      if (callback !== null) {
        if (wait) {
          onwait = callback(param);
        }
      }

      if (!onwait) this.main = false;
    },
    submit() {
      if (this.submitHandler !== null) {
        this.submitHandler();
      }
    }
  });

  Alpine.store('notif_modal', {
    main: false,
    type: "success",
    title: 'THIS IS TITLE',
    main_content: "this is primary content",
    second_content: "this is secondary content",
    btn_text: "OK",
    show(title, main_content, second_content, type, btn_text, callback) {
      this.title = title;
      this.main_content = main_content;
      this.second_content = second_content;
      this.type = type;
      this.btn_text = btn_text;
      if (callback !== null) {
        this.submitHandler = callback;
      }
      this.main = true;
    },
    hide(callback, param, wait = false) {
      let onwait = false;
      if (callback !== null) {
        if (wait) {
          onwait = callback(param);
        }
      }


      if (!onwait) this.main = false;

    },
    submit() {
      if (this.submitHandler !== null) {
        this.submitHandler();
      }
    }
  });

  Alpine.data('right_notif', () => ({
    notif: {},
    counter:1,
    bind: {
      self: {
        ['@add-notif.window']() {
          let param = this.$event.detail;
          this.addNotif(param);
        }
      },
      notif: {
      }
    },
    addNotif(param) {
      this.counter++;
      let counter = this.counter;
      let temp = {
        title: param.title,
        msg: param.msg,
        show: false,
        type: param.type
      };
      this.notif[counter] = temp;
      this.$nextTick(() => {
        this.notif[counter].show = true;
        if (param.timeout !== undefined) {
          setTimeout(() => {
            if (this.notif[counter] !== undefined) {
              this.notif[counter].show = false;
              setTimeout(() => {
                delete this.notif[counter];
              },_.toInteger(param.timeout));
            }
          }, _.toInteger(param.timeout));
        }
      });
    }
  }));
});