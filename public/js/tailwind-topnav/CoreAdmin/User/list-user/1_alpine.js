document.addEventListener('alpine:initializing', () => {
  Alpine.store('tbl', {
    column: [
      {
        name: 'id',
        label: 'User ID',
        filter: null,
        show: true
      },
      {
        name: 'username',
        label: 'Username',
        filter: { val: null, type: null }
      },
      {
        name: 'full_name',
        label: 'Full Name',
        filter: { val: null, type: null }
      },
      {
        name: 'email',
        label: 'Email',
        filter: { val: null, type: null }
      },
      {
        name: 'created_date',
        label: 'Created Date',
        filter: { val: null, type: null }
      },
      {
        name: 'main_role',
        label: 'Main Role',
        filter: { val: null, type: null }
      },
      {
        name: 'position',
        label: 'Position',
        filter: { val: null, type: null }
      },
      {
        name: 'status',
        label: 'Status',
        filter: { val: null, type: null }
      },
    ],
    data: [],
    opt: {
      perpage: 5,
      current_page: 1,
      total_page: 10
    },
    checked: function (idx, checked) {
      const me = this;
      me.data[idx].checked = checked;
    },
    prevPage: () => {
      const me = Alpine.store('tbl');
      if (me.opt.current_page > 1) {
        me.opt.current_page--;
      }
    },
    nextPage: () => {
      const me = Alpine.store('tbl');
      if (me.opt.current_page < me.opt.total_page) {
        me.opt.current_page++;
      }
    },
    reloadData: () => {
      // console.log(_url.get_users);
      let csrf = getCSRFToken();
      // console.log(csrf);
      fetchWithTimeout(
        _url.get_users,
        {
          method: 'POST',
          redirect: 'error',
          headers: {
            Accept: 'application/json',
            [csrf.name] : csrf.val
          }
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
    }
  });

  Alpine.store('module', {
    data: []
  });

  Alpine.store('layout', {
    data: []
  });

  Alpine.store('menu', {
    data: {}
  });

  Alpine.data('row_detail', () => ({
    selected_module: 'all',
    module_menus: [],
    module_data: [],
    layouts: [],
    init() {
      const me = this;
      const moduleStore = Alpine.store('module');
      const menuStore = Alpine.store('menu');
      const layoutStore = Alpine.store('layout');
      if (menuStore.data[me.selected_module] !== undefined) {
        me.module_menus = menuStore.data[me.selected_module];
      }
      if (layoutStore.data !== undefined) {
        me.layouts = layoutStore.data;
      }
      if (layoutStore.data !== undefined) {
        me.module_data = moduleStore.data;
      }
      
      me.$watch('selected_module', (value, oldValue) => {
        if (menuStore.data[me.selected_module] !== undefined) {
          me.module_menus = menuStore.data[me.selected_module];
          me.layouts = [];
          me.$nextTick(() => {
            if (layoutStore.data !== undefined) {
              me.layouts = layoutStore.data;
            } else {
              me.layouts = [];
            }
          });
        } else {
          me.module_menus = [];
          me.$nextTick(() => {
            me.layouts = [];
          });
        }
      });
      me.$nextTick(() => {
        if (menuStore.data[me.selected_module] !== undefined) {
          me.module_menus = menuStore.data[me.selected_module];
        }
      });
    },
    t3_layout: {
      ['x-init']() {
        const me = this;
        me.menus = [];
        if (me.module_menus[me.layout.id] !== undefined) {
          me.$nextTick(() => {
            me.menus = me.module_menus[me.layout.id];
          });
        }
      }
    },
    t3_ul0: {
      ['x-init']() {
        const me = this;
        // console.log(me.menus);
        me.mnu0 = [];
        if (me.menus !== undefined) {
          if (me.menus[0] !== undefined) {
            me.mnu0 = me.menus[0];
          }
        }
      }
    },
    t3_ul1: {
      ['x-init']() {
        const me = this;
        // console.log(me.menus);
        me.mnu1 = [];
        if (me.menus[1] !== undefined) {
          if (me.menus[1][me.menu0.id] !== undefined) {
            me.mnu1 = me.menus[1][me.menu0.id];
          }
        }
      }
    },
    t3_ul2: {
      ['x-init']() {
        const me = this;
        // console.log(me.menus[2],me.menu1.id);
        me.mnu2 = [];
        if (me.menus[2] !== undefined) {
          if (me.menus[2][me.menu1.id] !== undefined) {
            me.mnu2 = me.menus[2][me.menu1.id];
          }
        }
        // console.log(me.mnu2);
      }
    },
    t3_ul0_li: {
      ['x-init']() {
        const me = this;

        me.$watch('s0', (value, oldValue) => {
          // console.log(me.menu0.id,value);
          if (me.row.selected_menu.includes(me.menu0.id)) {
            if (value !== 1) {
              let tmp = me.row.selected_menu;
              let new_selected = _.remove(tmp, function(n) {
                return n !== me.menu0.id;
              });
              me.row.selected_menu = new_selected;
            }
          } else {
            if (value === 1) {
              let new_selected = me.row.selected_menu;
              new_selected.push(me.menu0.id);
              me.row.selected_menu = new_selected;
            }
          }
        });

        me.$watch('a0', (value, oldValue) => {
          // console.log(me.menu0.id,'a0',value);
          let foundTrue = false;
          let allTrue = 0;
          let totalChild = 0;
          for (const key in me.a0) {
            if (Object.hasOwnProperty.call(me.a0, key)) {
              const el = me.a0[key];
              totalChild++;
              if (el===1) {
                foundTrue = true;
                allTrue++;
              }
            }
          }

          me.s0 = null;
          me.$nextTick(() => {
            if (allTrue === totalChild) {
              me.s0 = 1;
            } else if (foundTrue) {
              me.s0 = 2;
            } else {
              me.s0 = 0;
            }
          });
        });

        let s0 = 0;
        if (me.row.selected_menu !== null) {
          if (me.row.selected_menu.includes(me.menu0.id)) {
            s0 = 1;
          };
        }
        me.s0 = s0;
      }
    },
    t3_ul1_li: {
      ['x-init']() {
        const me = this;
        me.$watch('s1', (value, oldValue) => {
          // console.log(me.menu1.id,me.menu0.id,'s1', value);
          let a0 = _.cloneDeep(me.a0);
          a0[me.menu1.id] = value;
          me.a0 = {};
          // console.log(me.menu1.id,me.menu0.id,a0);

          if (me.row.selected_menu.includes(me.menu1.id)) {
            if (value !== 1) {
              let tmp = me.row.selected_menu;
              let new_selected = _.remove(tmp, function(n) {
                return n !== me.menu1.id;
              });
              me.row.selected_menu = new_selected;
            }
          } else {
            if (value === 1) {
              let new_selected = me.row.selected_menu;
              new_selected.push(me.menu1.id);
              me.row.selected_menu = new_selected;
            }
          }
          // me.$nextTick(() => {
            me.a0 = a0;
          // });
        });

        me.$watch('a1', (value, oldValue) => {
          // console.log(me.menu1.id,me.menu0.id,'a1',value);
          let foundTrue = false;
          let allTrue = 0;
          let totalChild = 0;
          for (const key in me.a1) {
            if (Object.hasOwnProperty.call(me.a1, key)) {
              const el = me.a1[key];
              totalChild++;
              if (el===1) {
                foundTrue = true;
                allTrue++;
              }
            }
          }

          me.s1 = null;
          me.$nextTick(() => {
            if (allTrue === totalChild) {
              me.s1 = 1;
            } else if (foundTrue) {
              me.s1 = 2;
            } else {
              me.s1 = 0;
            }
          });
        });

        let s1 = 0;
        if (me.row.selected_menu !== null) {
          if (me.row.selected_menu.includes(me.menu1.id)) {
            s1 = 1;
          };
        }

        me.s1 = s1;
      }
    },
    t3_ul2_li: {
      ['x-init']() {
        const me = this;
        me.$watch('s2', (value, oldValue) => {
          // console.log(me.menu2.id,me.menu1.id,'s2', value);
          let a1 = _.cloneDeep(me.a1);
          a1[me.menu2.id] = value;
          me.a1 = {};
          // console.log(me.menu2.id,me.menu1.id,a1);

          if (me.row.selected_menu.includes(me.menu2.id)) {
            if (value !== 1) {
              let tmp = me.row.selected_menu;
              let new_selected = _.remove(tmp, function(n) {
                return n !== me.menu2.id;
              });
              me.row.selected_menu = new_selected;
            }
          } else {
            if (value === 1) {
              let new_selected = me.row.selected_menu;
              new_selected.push(me.menu2.id);
              me.row.selected_menu = new_selected;
            }
          }
          // me.$nextTick(() => {
            me.a1 = a1;
          // });
        });

        me.$watch('a2', (value, oldValue) => {
          // console.log(me.menu2.id,me.menu1.id,'a2',value);
          let foundTrue = false;
          let allTrue = 0;
          let totalChild = 0;
          for (const key in me.a2) {
            if (Object.hasOwnProperty.call(me.a2, key)) {
              const el = me.a2[key];
              totalChild++;
              if (el===1) {
                foundTrue = true;
                allTrue++;
              }
            }
          }

          me.s2 = null;
          me.$nextTick(() => {
            if (allTrue === totalChild) {
              me.s2 = 1;
            } else if (foundTrue) {
              me.s2 = 2;
            } else {
              me.s2 = 0;
            }
          });
        });

        let s2 = 0;
        if (me.row.selected_menu !== null) {
          if (me.row.selected_menu.includes(me.menu2.id)) {
            s2 = 1;
          };
        }

        me.s2 = s2;
      }
    }
  }));
});