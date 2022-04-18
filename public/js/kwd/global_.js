const setup = () => {
  const getTheme = () => {
    return false;
    if (window.localStorage.getItem('dark')) {
      return JSON.parse(window.localStorage.getItem('dark'))
    }
    return !!window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
  }

  const setTheme = (value) => {
    window.localStorage.setItem('dark', value)
  }

  const getColor = () => {
    if (window.localStorage.getItem('color')) {
      return window.localStorage.getItem('color')
    }
    return 'myred'
  }

  const setColors = (color) => {
    const root = document.documentElement
    root.style.setProperty('--color-primary', `var(--color-${color})`)
    root.style.setProperty('--color-primary-50', `var(--color-${color}-50)`)
    root.style.setProperty('--color-primary-100', `var(--color-${color}-100)`)
    root.style.setProperty('--color-primary-light', `var(--color-${color}-light)`)
    root.style.setProperty('--color-primary-lighter', `var(--color-${color}-lighter)`)
    root.style.setProperty('--color-primary-dark', `var(--color-${color}-dark)`)
    root.style.setProperty('--color-primary-darker', `var(--color-${color}-darker)`)
    this.selectedColor = color
    window.localStorage.setItem('color', color)
  }

  return {
    loading: true,
    isDark: getTheme(),
    color: getColor(),
    selectedColor: 'myred',
    toggleTheme() {
      this.isDark = !this.isDark
      setTheme(this.isDark)
    },
    setLightTheme() {
      this.isDark = false
      setTheme(this.isDark)
    },
    setDarkTheme() {
      this.isDark = true
      setTheme(this.isDark)
    },
    setColors,
    toggleSidbarMenu() {
      this.isSidebarOpen = !this.isSidebarOpen
    },
    isSettingsPanelOpen: false,
    openSettingsPanel() {
      this.isSettingsPanelOpen = true
      this.$nextTick(() => {
        this.$refs.settingsPanel.focus()
      })
    },
    isNotificationsPanelOpen: false,
    openNotificationsPanel() {
      this.isNotificationsPanelOpen = true
      this.$nextTick(() => {
        this.$refs.notificationsPanel.focus()
      })
    },
    isSearchPanelOpen: false,
    openSearchPanel() {
      this.isSearchPanelOpen = true
      this.$nextTick(() => {
        this.$refs.searchInput.focus()
      })
    },
    isMobileSubMenuOpen: false,
    openMobileSubMenu() {
      this.isMobileSubMenuOpen = true
      this.$nextTick(() => {
        this.$refs.mobileSubMenu.focus()
      })
    },
    isMobileMainMenuOpen: false,
    openMobileMainMenu() {
      this.isMobileMainMenuOpen = true
      this.$nextTick(() => {
        this.$refs.mobileMainMenu.focus()
      })
    },
  }
}

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

      if(!onwait)this.main = false;
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