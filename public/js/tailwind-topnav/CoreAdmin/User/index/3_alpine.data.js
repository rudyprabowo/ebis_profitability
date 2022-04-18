document.addEventListener('alpine:init', () => {
  Alpine.data('table', () => ({
    sorting: {
      id: {
        order: "none",
        priority: null
      },
      username: {
        order: "none",
        priority: null
      },
      full_name: {
        order: "none",
        priority: null
      },
      email: {
        order: "none",
        priority: null
      },
      status: {
        order: "none",
        priority: null
      },
      is_organic: {
        order: "none",
        priority: null
      },
      mobile_no: {
        order: "none",
        priority: null
      },
      employ_nik: {
        order: "none",
        priority: null
      },
      spv_nik: {
        order: "none",
        priority: null
      },
      telegram_id: {
        order: "none",
        priority: null
      },
    },
    /* -------------------- Store Current Active Sorting Field ------------------- */
    active_sort_field: null,
    bind: {
      /* ------------------------ Header Column Binding ------------------------ */
      col_header: {
        ['@click']() {
          // console.log(this.$el.dataset.field);
          this.changeSorting(this.$el.dataset.field);
        },
      },
      /* ------------------------ Column Status Binding ------------------------ */
      col_status: {
        ['@click']() {
          // console.log(this.$el.dataset.field);
          this.changeStatus(this.$el.dataset.idx);
        },
      },
      /* ------------------------ Column IS LDAP Binding ------------------------ */
      col_is_organic: {
        ['@click']() {
          // console.log(this.$el.dataset.field);
          this.changeOrganic(this.$el.dataset.idx);
        },
      },
      /* ------------------------ Button Action Remove Binding ------------------------ */
      col_act_remove: {
        ['@click']() {
          // console.log(this.$el.dataset.field);
          this.removeRow(this.$el.dataset.idx);
        },
      },
      /* --------------------------- Check All Binding --------------------------- */
      check_all: {
        ['@click']() {
          let checked = this.$el.checked;
          const userStore = Alpine.store('user');
          userStore.checkAll(checked);
          // console.log(this.$el.checked);
        },
      },
      /* -------------------------- Row Checkbox Binding ------------------------- */
      row_checkbox: {
        ['@click']() {
          let dataset = this.$el.dataset;
          let checked = this.$el.checked;
          const userStore = Alpine.store('user');
          userStore.changeCheck(dataset.idx,checked);
          // console.log({ dataset, checked });
        },
      },
      /* --------------------------- Table Body Binding -------------------------- */
      table_body: {
        ['@remove-all-user.window']() {
          console.log("remove all");
          this.removeAllRow();
        },
        ['@download-data.window']() {
          console.log("download all");
          filterData(1,true);
        },
      }
    },
    init() {
      filterData();
    },
    changeSorting(field) {
      let curSort = this.sorting[field].order;
      if (field !== this.active_sort_field && this.active_sort_field!==null) {
        this.sorting[this.active_sort_field].order = "none";
      }
      // console.log([field,this.active_sort_field,curSort]);
      if (curSort === "none") {
        this.sorting[field].order = "asc";
        this.active_sort_field = field;
      }else if (curSort === "asc") {
        this.sorting[field].order = "desc";
        this.active_sort_field = field;
      }else if (curSort === "desc") {
        this.sorting[field].order = "none";
        this.active_sort_field = null;
      }
      const sortingStore = Alpine.store('sorting');
      sortingStore.setData({
        field: this.active_sort_field,
        order: this.sorting[field].order
      });
      this.$nextTick(() => {
        filterData();
      });
      // console.log(this.active_sort_field);
    },
    changeStatus(idx) {
      // console.log(idx, this.$el.checked);
      let old_val = !this.$el.checked;
      let act = "<b class='text-green-700'>activate</b>";
      let btn = "Activate User";
      if (!this.$el.checked) {
        act = "<b class='text-red-700'>deactivate</b>";
        btn = "Deactivate User";
      }
      this.$el.checked = old_val;
      const confirmStore = Alpine.store('confirm_modal');
      const userStore = Alpine.store('user');
      let user = userStore.getCurrentDataByIndex(idx);
      confirmStore.show(
        `${btn}`, `Are you sure you want to ${act} <b>User ${user.id} - ${user.username}</b> ?`, "", "info", `${btn}`,
        () => {
          // this.$el.checked = !old_val;
          confirmStore.hide();
          changeStatus(user,this.$el, old_val);
        }
      );
    },
    changeOrganic(idx) {
      // console.log(idx, this.$el.checked);
      let old_val = !this.$el.checked;
      let act = "<b class='text-green-700'>change to Organic User</b>";
      let btn = "Activate Organic Flag";
      if (!this.$el.checked) {
        act = "<b class='text-red-700'>change to Non Organic User</b>";
        btn = "Deactivate Organic Flag";
      }
      this.$el.checked = old_val;
      const confirmStore = Alpine.store('confirm_modal');
      const userStore = Alpine.store('user');
      let user = userStore.getCurrentDataByIndex(idx);
      confirmStore.show(
        `${btn}`, `Are you sure you want to ${act} <b>User ${user.id} - ${user.username}</b> ?`, "", "info", `${btn}`,
        () => {
          // this.$el.checked = !old_val;
          confirmStore.hide();
          changeOrganic(user,this.$el, old_val);
        }
      );
    },
    removeRow(idx) {
      // console.log(idx, this.$el.checked);
      let act = "<b class='text-red-700'>remove</b>";
      let btn = "Remove User";
      const confirmStore = Alpine.store('confirm_modal');
      const userStore = Alpine.store('user');
      let user = userStore.getCurrentDataByIndex(idx);
      confirmStore.show(
        `${btn}`, `Are you sure you want to ${act} <b>User ${user.id} - ${user.username}</b> ?`, "", "remove", `${btn}`,
        () => {
          confirmStore.hide();
          removeUser(user);
        }
      );
    },
    removeAllRow(idx) {
      // console.log(idx, this.$el.checked);
      let act = "<b class='text-red-700'>remove</b>";
      let btn = "Remove Selected User";

      const userStore = Alpine.store('user');
      let users = userStore.getSelectedUser();
      // console.table(users);
      if (users.length > 0) {
        const confirmStore = Alpine.store('confirm_modal');
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Selected User</b> ?`, "", "remove", `${btn}`,
          () => {
            confirmStore.hide();
            removeMultiUser(users.map((v,k)=>{return v.id}));
          }
        );
      }
    }
  }));

  Alpine.data('create', () => ({
    /* ------------------------------ Modal Toggler ------------------------------ */
    show: false,
    title: "Create New User",
    route_opt: [],
    data: {
      id: null,
      username: null,
      email: null,
      full_name: null,
      status: false,
      pass: null,
      confirm_pass: null,
      is_organic: false,
      mobile_no: null,
      telegram_id: null,
      nik: null,
      spv: null,
      redirect_url: null,
      redirect_param: null,
      redirect_query: null,
      login_method: "DEFAULT"
    },
    msg: {
      username: "",
      email: "",
      full_name: "",
      pass: "",
      confirm_pass: "",
      mobile_no: "",
      telegram_id: "",
      nik: "",
      redirect_url: "",
      redirect_param: "",
      redirect_query: ""
    },
    bind: {
      self: {
        // ['x-init'](){
        //   console.log("init create");
        //   $watch('show', value => console.log(value));
        // },
        ['@show-create.window']() {
          this.showModal();
        },
        ['@show-edit.window']() {
          let idx = this.$event.detail;
          this.showEdit(idx);
        },
        ['@success-create.window']() {
          this.cancelCreate();
          filterData();
        },
        // ['@create-username-exist.window']() {
        //   // console.log(this.$event);
        //   let msg = this.$event.detail;
        //   this.msg.username = msg;
        // },
        // ['@create-email-exist.window']() {
        //   // console.log(this.$event);
        //   let msg = this.$event.detail;
        //   this.msg.email = msg;
        // },
        // ['@create-nik-exist.window']() {
        //   // console.log(this.$event);
        //   let msg = this.$event.detail;
        //   this.msg.nik = msg;
        // },
        // ['@create-mobile-exist.window']() {
        //   // console.log(this.$event);
        //   let msg = this.$event.detail;
        //   this.msg.mobile_no = msg;
        // },
        // ['@create-telegram-exist.window']() {
        //   // console.log(this.$event);
        //   let msg = this.$event.detail;
        //   this.msg.telegram_id = msg;
        // },
      },
      status: {
        label1: {
          [':class']() {
            return {
              'font-medium text-gray-900': !this.data.status,
              'text-gray-500': this.data.status
            };
          }
        },
        label2: {
          [':class']() {
            return {
              'font-medium text-green-900': this.data.status,
              'text-gray-500': !this.data.status
            };
          }
        },
        btn: {
          ['@click']() {
            this.data.status = !this.data.status;
          },
          [':class']() {
            return {
              'bg-gray-200': !this.data.status,
              'bg-green-600 focus:ring-green-500': this.data.status
            };
          }
        },
        btn_span:{
          [':class']() {
            return {
              'translate-x-0': !this.data.status,
              'translate-x-5': this.data.status
            };
          }
        }
      },
      is_organic: {
        label1: {
          [':class']() {
            return {
              'font-medium text-gray-900': !this.data.is_organic,
              'text-gray-500': this.data.is_organic
            };
          }
        },
        label2: {
          [':class']() {
            return {
              'font-medium text-green-900': this.data.is_organic,
              'text-gray-500': !this.data.is_organic
            };
          }
        },
        btn: {
          ['@click']() {
            this.data.is_organic = !this.data.is_organic;
            this.$nextTick(() => {
              if (tomsel['create-login_method'] !== undefined) {
                console.log(tomsel, this.data.is_organic);
                if (!this.data.is_organic) {
                  tomsel_dom["create-login_method"].value = "DEFAULT";
                  let evt = document.createEvent('HTMLEvents');
                  evt.initEvent('change', false, true);
                  tomsel_dom["create-login_method"].dispatchEvent(evt);
                }
                tomsel['create-login_method'].clearOptions();
                tomsel['create-login_method'].sync();
              }
            });
          },
          [':class']() {
            return {
              'bg-gray-200': !this.data.is_organic,
              'bg-green-600 focus:ring-green-500': this.data.is_organic
            };
          }
        },
        btn_span:{
          [':class']() {
            return {
              'translate-x-0': !this.data.is_organic,
              'translate-x-5': this.data.is_organic
            };
          }
        }
      },
      username: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.username === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.username !== ""
            };
          }
        }
      },
      email: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.email === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.email !== ""
            };
          }
        }
      },
      full_name: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.full_name === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.full_name !== ""
            };
          }
        }
      },
      pass: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.pass === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.pass !== ""
            };
          }
        }
      },
      confirm_pass: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.confirm_pass === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.confirm_pass !== ""
            };
          }
        }
      },
      mobile_no: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.mobile_no === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.mobile_no !== ""
            };
          }
        }
      },
      telegram_id: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.telegram_id === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.telegram_id !== ""
            };
          }
        }
      },
      nik: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.nik === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.nik !== ""
            };
          }
        }
      },
      redirect_url: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.redirect_url === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.redirect_url !== ""
            };
          }
        }
      },
      redirect_param: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.redirect_param === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.redirect_param !== ""
            };
          }
        }
      },
      redirect_query: {
        input: {
          [':class']() {
            return {
              'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.msg.redirect_query === "",
              'focus:ring-red-500 focus:border-red-500 border-red-300': this.msg.redirect_query !== ""
            };
          }
        }
      },
    },
    emptyMsg() {
      this.msg = {
        username: "",
        email: "",
        full_name: "",
        pass: "",
        confirm_pass: "",
        mobile_no: "",
        telegram_id: "",
        nik: "",
        redirect_url: "",
        redirect_param: "",
        redirect_query: ""
      };
    },
    emptyForm() {
      this.title = "Create New User";
      this.emptyMsg();
      this.data = {
        id: null,
        username: null,
        email: null,
        full_name: null,
        status: false,
        pass: null,
        confirm_pass: null,
        is_organic: false,
        mobile_no: null,
        telegram_id: null,
        nik: null,
        spv: null,
        redirect_url: null,
        redirect_param: null,
        redirect_query: null,
        login_method: "DEFAULT"
      };
    },
    submitCreate() {
      this.emptyMsg();
      let current_data = _.cloneDeep(this.data);
      // console.log(current_data);
      // this.hideModal();
      if (this.checkInputData(current_data)) {
        if (current_data.id !== null) {
          updateUser(current_data);
        } else {
          current_data = _.pickBy(current_data, (val, idx) => {
            return idx !== "id";
          });
          console.log(current_data);
          addUser(current_data);
        }
      }
    },
    checkInputData(data) {
      const mainLoader = Alpine.store('loader');
      mainLoader.show("checking input data...");
      let ret = false;
      validate.validators.alphanumCheck = MY_VALIDATE.alphanumCheck;
      validate.validators.alphanumSpaceCheck = MY_VALIDATE.alphanumSpaceCheck;
      validate.validators.isJSON = MY_VALIDATE.isJSON;
      validate.validators.isURLPath = MY_VALIDATE.isURLPath;
      const contraint = {
        username: {
          presence: true,
          length: {
            minimum: 3,
            maximum: 10
          },
          alphanumCheck: {}
        },
        email: {
          presence: true,
          email: true
        },
        full_name: {
          presence: true,
          alphanumSpaceCheck: {}
        },
        pass: {
          presence: true,
          length: {
            minimum: 3
          }
        },
        confirm_pass: {
          presence: true,
          equality: "pass"
        },
        mobile_no: {
          format: {
            pattern: "[0-9]+",
            flags: "i",
            message: "not valid mobile number"
          },
          length: {
            minimum: 10,
            message: "not valid mobile number"
          }
        },
        telegram_id: {
          format: {
            pattern: "[0-9\-]+",
            flags: "i",
            message: "not valid telegram id"
          },
          length: {
            minimum: 4,
            message: "not valid telegram id"
          }
        },
        nik: {
          format: {
            pattern: "[0-9]+",
            flags: "i",
            message: "not valid nik format"
          },
          length: {
            minimum: 4,
            message: "not valid nik format"
          }
        },
        redirect_url: {
          isURLPath: {}
        },
        redirect_param: {
          isJSON: {}
        },
        redirect_query: {
          isJSON: {}
        }
      };
      let msg = validate(data, contraint, {fullMessages:false});
      // console.log(msg);
      if (msg !== undefined) {
        let _msg = _.cloneDeep(this.msg);
        this.msg = Object.assign(_msg, msg);
      } else {
        ret = true;
      }
      mainLoader.hide();
      return ret;
    },
    cancelCreate() {
      // if (this.data.id !== null) {
        this.emptyForm();
      // }
      // for (const key in this.current_data) {
      //   this.data[key] = this.current_data[key];
      //   if (tomsel["create-" + key] !== undefined) {
      //     tomsel_dom["create-" + key].value = (this.current_data[key]!==null)?this.current_data[key]:"none";
      //     let evt = document.createEvent('HTMLEvents');
      //     evt.initEvent('change', false, true);
      //     tomsel_dom["create-" + key].dispatchEvent(evt);
      //   }
      //   if (tomtag["create-" + key] !== undefined) {
      //     tomtag_dom["create-" + key].value = this.current_data[key];
      //     let evt = document.createEvent('HTMLEvents');
      //     evt.initEvent('change', false, true);
      //     tomtag_dom["create-" + key].dispatchEvent(evt);
      //   }
      // }
      this.$nextTick(() => {
        this.hideModal();
      });
    },
    showEdit(idx) {
      const user_dataStore = Alpine.store('user');
      let _data = _.cloneDeep(user_dataStore.getCurrentDataByIndex(idx));
      console.table(_data);
      this.title = "Edit User - "+_data.id;
      this.data = {
        id: _data.id,
        name: _data.name,
        status: _.toInteger(_data.status)===1,
        session: _data.session_name
      };
      this.showModal();
    },
    showModal() {
      fetchWithTimeout("/core-admin/api/getroute",
        {
          method: 'GET',
          headers: {
              Accept: 'application/json',
          }
      },
      10000)
      .then((response) => response.json())
      .then((json) => {
          // console.table(json);
          let firstOpt = null
          if (Array.isArray(json)) {
            this.route_opt = json.filter((itm, idx) => {
              // if (idx === 0) firstOpt = itm.id;
              return parseInt(itm.status) === 1;
            });
            // this.route_opt.unshift({id: "none", route: "None"});
          }
          this.$nextTick(() => {
            if (tomsel['create-redirect_route'] !== undefined) {
              tomsel['create-redirect_route'].clearOptions();
              tomsel['create-redirect_route'].sync();
              // tomsel['create-redirect_route'].setValue("none");
            }
            this.show = true;
          });
      })
      .catch((error) => {
          console.log(error);
      });
    },
    hideModal() {
      this.show = false;
    }
  }));

  Alpine.data('filter', () => ({
    /* ------------------------------ Modal Toggler ------------------------------ */
    show: false,
    field: {
      id: {
        cond: "none",
        val: null,
        label: "USER ID",
        is_numeric: true,
        type: 'text'
      },
      username: {
        cond: "none",
        val: null,
        label: "USER NAME",
        type: 'text'
      },
      full_name: {
        cond: "none",
        val: null,
        label: "FULL NAME",
        type: 'text'
      },
      email: {
        cond: "none",
        val: null,
        label: "EMAIL",
        type: 'text'
      },
      status: {
        cond: "none",
        val: null,
        label: "STATUS",
        type: 'select',
        is_equal: true,
        select: [
          {
            val: 1,
            label: 'Active'
          },
          {
            val: 0,
            label: 'Not Active'
          },
          {
            val: 2,
            label: 'Banned'
          },
        ]
      },
      is_organic: {
        cond: "none",
        val: null,
        label: "IS ORGANIC",
        type: 'select',
        is_equal: true,
        select: [
          {
            val: 1,
            label: 'Active'
          },
          {
            val: 0,
            label: 'Not Active'
          }
        ]
      },
      mobile_no: {
        cond: "none",
        val: null,
        label: "MOBILE NO",
        type: 'text'
      },
      employ_nik: {
        cond: "none",
        val: null,
        label: "NIK",
        type: 'text'
      },
      spv_nik: {
        cond: "none",
        val: null,
        label: "SPV NIK",
        type: 'text'
      },
      telegram_id: {
        cond: "none",
        val: null,
        label: "TELEGRAM ID",
        type: 'text'
      },
    },
    event: {
      ['@show-filter.window']() {
        this.showModal();
      },
    },
    getValues() {
      let values = _.cloneDeep(this.field);
      values = _.mapValues(values, function (o) {
        if (o.is_equal) {
          if (o.val !== "none") {
            o.cond = "equal";
          } else {
            o.cond = "none";
          }
        }
        let _val = null;
        if (o.val !== "none" && o.val !== null) {
          _val = _.cloneDeep(o.val);
          _val = _val.split(",");
          _val = _val.map(v => {
            return v.trim();
          });
        }
        return { cond: o.cond, val: _val };
      });
      // console.table(values);
      return values;
    },
    updateFilter() {
      let val = this.getValues();
      for (const key in val) {
        if (val[key].cond === "none") {
          this.field[key].val = null;
          val[key].val = null;
          if (tomsel["filterval-" + key] !== undefined) {
            tomsel_dom["filterval-" + key].value = "none";
            let evt = document.createEvent('HTMLEvents');
            evt.initEvent('change', false, true);
            tomsel_dom["filterval-" + key].dispatchEvent(evt);
          }
          if (tomtag["filterval-" + key] !== undefined) {
            tomtag_dom["filterval-" + key].value = this.field[key].val;
            let evt = document.createEvent('HTMLEvents');
            evt.initEvent('change', false, true);
            tomtag_dom["filterval-" + key].dispatchEvent(evt);
          }
        }
      }

      const filterStore = Alpine.store('filter');
      filterStore.setData(val);
      filterData();
      this.hideModal();
    },
    cancelFilter() {
      const filterStore = Alpine.store('filter');
      // console.log(filterStore.data);
      for (const key in filterStore.data) {
        this.field[key].cond = filterStore.data[key].cond;
        this.field[key].val = filterStore.data[key].val;
        // console.log(key,this.field[key]);
        if (tomsel["filtercond-" + key] !== undefined) {
          tomsel_dom["filtercond-" + key].value = (this.field[key].cond!==null)?this.field[key].cond:"none";
          let evt = document.createEvent('HTMLEvents');
          evt.initEvent('change', false, true);
          tomsel_dom["filtercond-" + key].dispatchEvent(evt);
        }
        if (tomsel["filterval-" + key] !== undefined) {
          tomsel_dom["filterval-" + key].value = (this.field[key].val!==null)?this.field[key].val:"none";
          let evt = document.createEvent('HTMLEvents');
          evt.initEvent('change', false, true);
          tomsel_dom["filterval-" + key].dispatchEvent(evt);
        }
        if (tomtag["filterval-" + key] !== undefined) {
          tomtag_dom["filterval-" + key].value = this.field[key].val;
          let evt = document.createEvent('HTMLEvents');
          evt.initEvent('change', false, true);
          tomtag_dom["filterval-" + key].dispatchEvent(evt);
        }
      }
      this.$nextTick(() => {
        this.hideModal();
      });
    },
    showModal() {
      this.show = true;
    },
    hideModal() {
      this.show = false;
    }
  }));

  Alpine.data('paging', () => ({
    hasNext: false,
    bind: {
      prev_btn: {
        ['@click']() {
          const pagingStore = Alpine.store('paging');
          pagingStore.data.current_page = pagingStore.data.current_page - 1;
          filterData();
        }
      },
      next_btn: {
        ['@click']() {
          const pagingStore = Alpine.store('paging');
          pagingStore.data.current_page = pagingStore.data.current_page+1;
          filterData();
        },
        ['@has-next.window']() {
          this.hasNext = this.$event.detail;
        },
      },
      limit_input: {
        ['@keyup.enter']() {
          filterData();
        }
      },
      page_input: {
        ['@keyup.enter']() {
          const pagingStore = Alpine.store('paging');
          // console.log(pagingStore);
          if (pagingStore.data.current_page > pagingStore.data.total_page) {
            pagingStore.data.current_page = pagingStore.data.total_page;
          }else if (pagingStore.data.current_page < 1) {
            pagingStore.data.current_page = 1;
          }
          filterData();
        }
      }
    }
  }));

  Alpine.data('upload', () => ({
    /* ------------------------------ Modal Toggler ------------------------------ */
    show: false,
    show_upload: true,
    show_progress: false,
    on_upload: false,
    filename: "",
    status: "",
    size: "",
    dropstat: null,
    allow: [
      ".xls", ".xlsx",
      // ".csv",
      "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      "application/vnd.ms-excel",
      // "text/csv"
    ],
    maxsize: (1048576 * 10),
    progress: 0,
    bind: {
      self: {
        ['@show-upload.window']() {
          this.showModal();
        },
        ['@progress-upload.window']() {
          let param = this.$event.detail;
          this.status = "uploaded " + param.loaded + " bytes of " + param.total;
          var percent = Math.round((param.loaded / param.total) * 100);
          this.progress = percent;
          this.$refs.progress_bar.style.width = percent + "%";
          if (percent >= 100) {
            this.status = "uploaded";
          }
        },
        ['@stop-upload.window']() {
          this.status = this.$event.detail;
          this.abortUpload();
        },
        ['@complete-upload.window']() {
          this.status = "please wait, checking file content...";
          xhr_upload = null;
          this.status = "finished";
          xhr_upload = null;
          this.progress = 0;
          this.$refs.progress_bar.style.width = "0%";
          this.on_upload = false;
          this.cancelUpload();
          window.dispatchEvent(new CustomEvent('add-notif', {
            detail: { type:"success", title: "File Uploaded", msg: `Success upload file`, timeout:5000 }
          }));
          filterData(0);
        },
      },
      drop_area: {
        ['@dragenter.prevent']() {
          // console.log('dragenter', this.$event);
          // this.dropstat = 'dragenter';
        },
        ['@dragleave.prevent']() {
          // console.log('dragleave',this.$event);
          this.dropstat = null;
        },
        ['@dragover.prevent']() {
          this.dropstat = 'dragenter';
        },
        ['@drop.prevent']() {
          // console.log('drop',this.$event);
          this.dropstat = 'null';
          // console.log(this.$event.dataTransfer.types);
          // console.log(this.$event.dataTransfer.items);
          // console.log(this.$event.dataTransfer.files);
          // console.log(this.$refs.input_file);
          this.$refs.input_file.files = this.$event.dataTransfer.files;
          this.$refs.input_file.dispatchEvent(new Event('change'));
        },
      },
      remove_btn: {
        ['@click']() {
          this.show_progress = false;
          this.$nextTick(() => {
            this.show_upload = true;
            this.emptyForm();
          });
        }
      },
      input_file: {
        ["@change"]() {
          // console.log(this.$el.value);
          var file = this.$el.files;
          if (file.length > 0) {
            file = file[0];
          }
          // console.log(file);
          if (file.size > this.maxsize) {
            this.emptyForm();
            window.dispatchEvent(new CustomEvent('add-notif', {
              detail: { type:"failed", title: "Invalid File", msg: `File too large, <b>max file size 10MB</b>`, timeout:5000 }
            }));
          } else if (!_.includes(this.allow, file.type)) {
            this.emptyForm();
            window.dispatchEvent(new CustomEvent('add-notif', {
              detail: { type:"failed", title: "Invalid File", msg: `Invalid file type, <b>accepted file format (xlsx, xls)</b>`, timeout:5000 }
            }));
          } else {
            file_upload = file;
            this.filename = file.name;
            this.size = byteConvert(file.size);
            this.progress = 0;
            this.$refs.progress_bar.style.width = "0%";
            this.show_progress = true;
            this.$nextTick(() => {
              this.show_upload = false;
            });
          }
        }
      }
    },
    ajax: {
      url: "/core-admin/manage-script/user/upload",
      method: "POST",
      progressHandler: (event) => {
        window.dispatchEvent(new CustomEvent('progress-upload', {detail:{loaded:event.loaded,total:event.total}}) );
      },
      completeHandler: (event) => {
        window.dispatchEvent(new CustomEvent('complete-upload') );
      },
      errorHandler: (event) => {
        window.dispatchEvent(new CustomEvent('stop-upload', {detail:"failed"}) );
      },
      timeoutHandler: (event) => {
        window.dispatchEvent(new CustomEvent('stop-upload', {detail:"timeout"}) );
      },
      abortHandler: (event) => {
        window.dispatchEvent(new CustomEvent('stop-upload', {detail:"aborted"}) );
      }
    },
    emptyForm() {
      file_upload = null;
      xhr_upload = null;
      this.status = "";
      this.filename = "";
      this.size = "";
      this.progress = 0;
      this.$refs.progress_bar.style.width = "0%";
      this.$refs.form_upload.reset();
      this.show_progress = false;
      this.$nextTick(() => {
        this.show_upload = true;
      });
    },
    abortUpload() {
      if (xhr_upload !== null) xhr_upload.abort();
      xhr_upload = null;
      this.progress = 0;
      this.$refs.progress_bar.style.width = "0%";
      this.on_upload = false;
    },
    startUpload() {
      this.status = "uploading";
      if (xhr_upload !== null) xhr_upload.abort();
      xhr_upload = null;
      this.on_upload = true;
      let ajax = this.ajax;
      var formdata = new FormData();
      formdata.append("file", file_upload, this.filename);
      let _head = getCSRFToken();
      let header = {};
      header[_head.name] = _head.val;
      xhr_upload = callXHR(ajax.url, ajax.method, formdata, header,
        () => { }, ajax.progressHandler, () => { }, ajax.completeHandler,
        ajax.errorHandler, ajax.timeoutHandler, ajax.abortHandler);
    },
    cancelUpload() {
      if (!this.on_upload) {
        this.hideModal();
        this.$nextTick(() => {
          this.emptyForm();
        });
      }
    },
    showModal() {
      this.show = true;
    },
    hideModal() {
      this.show = false;
    }
  }));

});