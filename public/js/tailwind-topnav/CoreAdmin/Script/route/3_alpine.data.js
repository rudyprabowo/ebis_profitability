document.addEventListener('alpine:init', () => {
    Alpine.data('table', () => ({
      sorting: {
        id: {
          order: "none",
          priority: null
        },
        name: {
          order: "none",
          priority: null
        },
        parent: {
          order: "none",
          priority: null
        },
        status: {
          order: "none",
          priority: null
        },
        type: {
          order: "none",
          priority: null
        },
        route: {
          order: "none",
          priority: null
        },
        action: {
          order: "none",
          priority: null
        },
        title: {
          order: "none",
          priority: null
        },
        show_title: {
          order: "none",
          priority: null
        },
        may_terminate: {
          order: "none",
          priority: null
        },
        is_logging: {
          order: "none",
          priority: null
        },
        method: {
          order: "none",
          priority: null
        },
        is_caching: {
          order: "none",
          priority: null
        },
        layout: {
          order: "none",
          priority: null
        },
        is_public: {
          order: "none",
          priority: null
        },
        is_api: {
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
        /* ------------------------ Column May Terminate Binding ------------------------ */
        col_may_terminate: {
          ['@click']() {
            // console.log(this.$el.dataset.field);
            this.changeMay_terminate(this.$el.dataset.idx);
          },
        },
        /* ------------------------ Column Show Title Binding ------------------------ */
        col_show_title: {
          ['@click']() {
            // console.log(this.$el.dataset.field);
            this.changeShow_title(this.$el.dataset.idx);
          },
        },
        /* ------------------------ Column Is Logging Binding ------------------------ */
        col_is_logging: {
          ['@click']() {
            // console.log(this.$el.dataset.field);
            this.changeIs_logging(this.$el.dataset.idx);
          },
        },
        /* ------------------------ Column Is Caching Binding ------------------------ */
        col_is_caching: {
          ['@click']() {
            // console.log(this.$el.dataset.field);
            this.changeIs_caching(this.$el.dataset.idx);
          },
        },
        /* ------------------------ Column Is Public Binding ------------------------ */
        col_is_public: {
          ['@click']() {
            // console.log(this.$el.dataset.field);
            this.changeIs_public(this.$el.dataset.idx);
          },
        },
        /* ------------------------ Column Is API Binding ------------------------ */
        col_is_api: {
          ['@click']() {
            // console.log(this.$el.dataset.field);
            this.changeIs_api(this.$el.dataset.idx);
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
            const routeStore = Alpine.store('route');
            routeStore.checkAll(checked);
            // console.log(this.$el.checked);
          },
        },
        /* -------------------------- Row Checkbox Binding ------------------------- */
        row_checkbox: {
          ['@click']() {
            let dataset = this.$el.dataset;
            let checked = this.$el.checked;
            const routeStore = Alpine.store('route');
            routeStore.changeCheck(dataset.idx,checked);
            // console.log({ dataset, checked });
          },
        },
        /* --------------------------- Table Body Binding -------------------------- */
        table_body: {
          ['@remove-all-route.window']() {
            console.log("remove all");
            this.removeAllRow();
          },
        }
      },
      init() {
        reloadData();
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
        let btn = "Activate Route";
        if (!this.$el.checked) {
          act = "<b class='text-red-700'>deactivate</b>";
          btn = "Deactivate Route";
        }
        this.$el.checked = old_val;
        const confirmStore = Alpine.store('confirm_modal');
        const routeStore = Alpine.store('route');
        let route = routeStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Route ${route.id} - ${route.name}</b> ?`, "", "info", `${btn}`,
          () => {
            // this.$el.checked = !old_val;
            confirmStore.hide();
            changeStatus(route,this.$el, old_val);
          }
        );
      },
      changeShow_title(idx) {
        // console.log(idx, this.$el.checked);
        let old_val = !this.$el.checked;
        let act = "<b class='text-green-700'>activate</b>";
        let btn = "Activate Route";
        if (!this.$el.checked) {
          act = "<b class='text-red-700'>deactivate</b>";
          btn = "Deactivate Route";
        }
        this.$el.checked = old_val;
        const confirmStore = Alpine.store('confirm_modal');
        const routeStore = Alpine.store('route');
        let route = routeStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Route ${route.id} - ${route.name}</b> ?`, "", "info", `${btn}`,
          () => {
            // this.$el.checked = !old_val;
            confirmStore.hide();
            changeShow_title(route,this.$el, old_val);
          }
        );
      },
      changeMay_terminate(idx) {
        // console.log(idx, this.$el.checked);
        let old_val = !this.$el.checked;
        let act = "<b class='text-green-700'>activate</b>";
        let btn = "Activate Route";
        if (!this.$el.checked) {
          act = "<b class='text-red-700'>deactivate</b>";
          btn = "Deactivate Route";
        }
        this.$el.checked = old_val;
        const confirmStore = Alpine.store('confirm_modal');
        const routeStore = Alpine.store('route');
        let route = routeStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Route ${route.id} - ${route.name}</b> ?`, "", "info", `${btn}`,
          () => {
            // this.$el.checked = !old_val;
            confirmStore.hide();
            changeMay_terminate(route,this.$el, old_val);
          }
        );
      },
      changeIs_caching(idx) {
        // console.log(idx, this.$el.checked);
        let old_val = !this.$el.checked;
        let act = "<b class='text-green-700'>activate</b>";
        let btn = "Activate Route";
        if (!this.$el.checked) {
          act = "<b class='text-red-700'>deactivate</b>";
          btn = "Deactivate Route";
        }
        this.$el.checked = old_val;
        const confirmStore = Alpine.store('confirm_modal');
        const routeStore = Alpine.store('route');
        let route = routeStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Route ${route.id} - ${route.name}</b> ?`, "", "info", `${btn}`,
          () => {
            // this.$el.checked = !old_val;
            confirmStore.hide();
            changeIs_caching(route,this.$el, old_val);
          }
        );
      },
      changeIs_logging(idx) {
        // console.log(idx, this.$el.checked);
        let old_val = !this.$el.checked;
        let act = "<b class='text-green-700'>activate</b>";
        let btn = "Activate Route";
        if (!this.$el.checked) {
          act = "<b class='text-red-700'>deactivate</b>";
          btn = "Deactivate Route";
        }
        this.$el.checked = old_val;
        const confirmStore = Alpine.store('confirm_modal');
        const routeStore = Alpine.store('route');
        let route = routeStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Route ${route.id} - ${route.name}</b> ?`, "", "info", `${btn}`,
          () => {
            // this.$el.checked = !old_val;
            confirmStore.hide();
            changeIs_logging(route,this.$el, old_val);
          }
        );
      },
      changeIs_public(idx) {
        // console.log(idx, this.$el.checked);
        let old_val = !this.$el.checked;
        let act = "<b class='text-green-700'>activate</b>";
        let btn = "Activate Route";
        if (!this.$el.checked) {
          act = "<b class='text-red-700'>deactivate</b>";
          btn = "Deactivate Route";
        }
        this.$el.checked = old_val;
        const confirmStore = Alpine.store('confirm_modal');
        const routeStore = Alpine.store('route');
        let route = routeStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Route ${route.id} - ${route.name}</b> ?`, "", "info", `${btn}`,
          () => {
            // this.$el.checked = !old_val;
            confirmStore.hide();
            changeIs_public(route,this.$el, old_val);
          }
        );
      },
      changeIs_api(idx) {
        // console.log(idx, this.$el.checked);
        let old_val = !this.$el.checked;
        let act = "<b class='text-green-700'>activate</b>";
        let btn = "Activate Route";
        if (!this.$el.checked) {
          act = "<b class='text-red-700'>deactivate</b>";
          btn = "Deactivate Route";
        }
        this.$el.checked = old_val;
        const confirmStore = Alpine.store('confirm_modal');
        const routeStore = Alpine.store('route');
        let route = routeStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Route ${route.id} - ${route.name}</b> ?`, "", "info", `${btn}`,
          () => {
            // this.$el.checked = !old_val;
            confirmStore.hide();
            changeIs_api(route,this.$el, old_val);
          }
        );
      },
      removeRow(idx) {
        // console.log(idx, this.$el.checked);
        let act = "<b class='text-red-700'>remove</b>";
        let btn = "Remove Route";
        const confirmStore = Alpine.store('confirm_modal');
        const routeStore = Alpine.store('route');
        let route = routeStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Route ${route.id} - ${route.name}</b> ?`, "", "remove", `${btn}`,
          () => {
            confirmStore.hide();
            removeRoute(route);
          }
        );
      },
      removeAllRow(idx) {
        // console.log(idx, this.$el.checked);
        let act = "<b class='text-red-700'>remove</b>";
        let btn = "Remove Selected Route";
  
        const routeStore = Alpine.store('route');
        let routes = routeStore.getSelectedRoute();
        // console.table(routes);
        if (routes.length > 0) {
          const confirmStore = Alpine.store('confirm_modal');
          confirmStore.show(
            `${btn}`, `Are you sure you want to ${act} <b>Selected Route</b> ?`, "", "remove", `${btn}`,
            () => {
              confirmStore.hide();
              removeMultiRoute(routes.map((v,k)=>{return v.id}));
            }
          );
        }
      }
    }));

    Alpine.data('create', () => ({
        /* ------------------------------ Modal Toggler ------------------------------ */
        show: false,
        title: "Create New Route",
        data: {
          id: null,
          name: null,
          parent: null,
          parent_id: null,
          status: false,
          type: null,
          route: null,
          action: null,
          action_id: null,
          title: null,
          show_title: false,
          may_terminate: false,
          is_logging: false,
          method: null,
          is_caching: false,
          layout: null,
          layout_id: null,
          is_public: false,
          is_api: false,
        },
        name_msg: "",
        // opt_method: { value: ['PUT','GET','POST', 'DELETE']},
        select_opt :["GET", "PUT", "POST", "DELETE"],
        bind: {
          self: {
            ['@show-create.window']() {
              this.showModal();
            },
            ['@show-edit.window']() {
              let idx = this.$event.detail;
              this.showEdit(idx);
            },
            ['@success-create.window']() {
              this.cancelCreate();
              reloadData();
            },
            ['@create-name-exist.window']() {
              console.log(this.$event);
              let msg = this.$event.detail;
              this.name_msg = msg;
            },
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
          show_title: {
            label1: {
              [':class']() {
                return {
                  'font-medium text-gray-900': !this.data.show_title,
                  'text-gray-500': this.data.show_title
                };
              }
            },
            label2: {
              [':class']() {
                return {
                  'font-medium text-green-900': this.data.show_title,
                  'text-gray-500': !this.data.show_title
                };
              }
            },
            btn: {
              ['@click']() {
                this.data.show_title = !this.data.show_title;
              },
              [':class']() {
                return {
                  'bg-gray-200': !this.data.show_title,
                  'bg-green-600 focus:ring-green-500': this.data.show_title
                };
              }
            },
            btn_span:{
              [':class']() {
                return {
                  'translate-x-0': !this.data.show_title,
                  'translate-x-5': this.data.show_title
                };
              }
            }
          },
          may_terminate: {
            label1: {
              [':class']() {
                return {
                  'font-medium text-gray-900': !this.data.may_terminate,
                  'text-gray-500': this.data.may_terminate
                };
              }
            },
            label2: {
              [':class']() {
                return {
                  'font-medium text-green-900': this.data.may_terminate,
                  'text-gray-500': !this.data.may_terminate
                };
              }
            },
            btn: {
              ['@click']() {
                this.data.may_terminate = !this.data.may_terminate;
              },
              [':class']() {
                return {
                  'bg-gray-200': !this.data.may_terminate,
                  'bg-green-600 focus:ring-green-500': this.data.may_terminate
                };
              }
            },
            btn_span:{
              [':class']() {
                return {
                  'translate-x-0': !this.data.may_terminate,
                  'translate-x-5': this.data.may_terminate
                };
              }
            }
          },
          is_caching: {
            label1: {
              [':class']() {
                return {
                  'font-medium text-gray-900': !this.data.is_caching,
                  'text-gray-500': this.data.is_caching
                };
              }
            },
            label2: {
              [':class']() {
                return {
                  'font-medium text-green-900': this.data.is_caching,
                  'text-gray-500': !this.data.is_caching
                };
              }
            },
            btn: {
              ['@click']() {
                this.data.is_caching = !this.data.is_caching;
              },
              [':class']() {
                return {
                  'bg-gray-200': !this.data.is_caching,
                  'bg-green-600 focus:ring-green-500': this.data.is_caching
                };
              }
            },
            btn_span:{
              [':class']() {
                return {
                  'translate-x-0': !this.data.is_caching,
                  'translate-x-5': this.data.is_caching
                };
              }
            }
          },
          is_logging: {
            label1: {
              [':class']() {
                return {
                  'font-medium text-gray-900': !this.data.is_logging,
                  'text-gray-500': this.data.is_logging
                };
              }
            },
            label2: {
              [':class']() {
                return {
                  'font-medium text-green-900': this.data.is_logging,
                  'text-gray-500': !this.data.is_logging
                };
              }
            },
            btn: {
              ['@click']() {
                this.data.is_logging = !this.data.is_logging;
              },
              [':class']() {
                return {
                  'bg-gray-200': !this.data.is_logging,
                  'bg-green-600 focus:ring-green-500': this.data.is_logging
                };
              }
            },
            btn_span:{
              [':class']() {
                return {
                  'translate-x-0': !this.data.is_logging,
                  'translate-x-5': this.data.is_logging
                };
              }
            }
          },
          is_public: {
            label1: {
              [':class']() {
                return {
                  'font-medium text-gray-900': !this.data.is_public,
                  'text-gray-500': this.data.is_public
                };
              }
            },
            label2: {
              [':class']() {
                return {
                  'font-medium text-green-900': this.data.is_public,
                  'text-gray-500': !this.data.is_public
                };
              }
            },
            btn: {
              ['@click']() {
                this.data.is_public = !this.data.is_public;
              },
              [':class']() {
                return {
                  'bg-gray-200': !this.data.is_public,
                  'bg-green-600 focus:ring-green-500': this.data.is_public
                };
              }
            },
            btn_span:{
              [':class']() {
                return {
                  'translate-x-0': !this.data.is_public,
                  'translate-x-5': this.data.is_public
                };
              }
            }
          },
          is_api: {
            label1: {
              [':class']() {
                return {
                  'font-medium text-gray-900': !this.data.is_api,
                  'text-gray-500': this.data.is_api
                };
              }
            },
            label2: {
              [':class']() {
                return {
                  'font-medium text-green-900': this.data.is_api,
                  'text-gray-500': !this.data.is_api
                };
              }
            },
            btn: {
              ['@click']() {
                this.data.is_api = !this.data.is_api;
              },
              [':class']() {
                return {
                  'bg-gray-200': !this.data.is_api,
                  'bg-green-600 focus:ring-green-500': this.data.is_api
                };
              }
            },
            btn_span:{
              [':class']() {
                return {
                  'translate-x-0': !this.data.is_api,
                  'translate-x-5': this.data.is_api
                };
              }
            }
          },
          name: {
            input: {
              [':class']() {
                return {
                  'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.name_msg === "",
                  'focus:ring-red-500 focus:border-red-500 border-red-300': this.name_msg !== ""
                };
              }
            }
          }
        },
        emptyForm() {
          this.title = "Create New Route";
          this.name_msg = "";
          this.data = {
            id: null,
            name: null,
            parent: null,
            status: false,
            type: null,
            route: null,
            action: null,
            title: null,
            show_title: false,
            may_terminate: false,
            is_logging: false,
            method: null,
            is_caching: false,
            layout: null,
            is_public: false,
            is_api: false,
          };
        },
        submitCreate() {
          this.name_msg = "";
          let current_data = _.cloneDeep(this.data);
          // console.log(current_data);
          this.hideModal();
          if (current_data.name === "" || current_data.name === null) {
            this.name_msg = "Route name can not be null";
          } else {
            if (current_data.id !== null) {
              updateRoute(current_data);
            } else {
              current_data = _.pickBy(current_data, (val, idx) => {
                return idx !== "id";
              });
              // console.log(current_data);
              addRoute(current_data);
            }
          }
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
          const route_dataStore = Alpine.store('route');
          let _data = _.cloneDeep(route_dataStore.getCurrentDataByIndex(idx));
          // console.log(_data.method);
          // console.table(_data);
          this.title = "Edit Route - "+_data.id;
          this.data = {
            id: _data.id,
            name: _data.name,
            parent: _data.parent,
            parent_id: _data.parent_id,
            status: _.toInteger(_data.status)===1,
            type: _data.type,
            route: _data.route,
            action: _data.action,
            action_id: _data.action_id,
            title: _data.title,
            show_title: _.toInteger(_data.show_title)===1,
            may_terminate: _.toInteger(_data.may_terminate)===1,
            is_logging: _.toInteger(_data.is_logging)===1,
            method: _data.method,
            is_caching: _.toInteger(_data.is_caching)===1,
            layout: _data.layout,
            layout_id: _data.layout_id,
            is_public: _.toInteger(_data.is_public)===1,
            is_api: _.toInteger(_data.is_api)===1,
          };
          this.showModal();
        },
        showModal() {
          this.show = true;
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
            label: "ROUTE ID",
            type: 'text'
          },
          name: {
            cond: "none",
            val: null,
            label: "ROUTE NAME",
            type: 'text'
          },
          parent: {
            cond: "none",
            val: null,
            label: "PARENT",
            type: 'text'
          },
          status: {
            cond: "none",
            val: null,
            label: "STATUS",
            type: 'select',
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
          type: {
            cond: "none",
            val: null,
            label: "TYPE",
            type: 'text'
          },
          route: {
            cond: "none",
            val: null,
            label: "ROUTE",
            type: 'text'
          },
          action: {
            cond: "none",
            val: null,
            label: "ACTION",
            type: 'text'
          },
          title: {
            cond: "none",
            val: null,
            label: "TITLE",
            type: 'text'
          },
          show_title: {
            cond: "none",
            val: null,
            label: "SHOW TITLE",
            type: 'select',
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
          may_terminate: {
            cond: "none",
            val: null,
            label: "MAY TERMINATE",
            type: 'select',
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
          is_logging: {
            cond: "none",
            val: null,
            label: "IS LOGGING",
            type: 'select',
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
          method: {
            cond: "none",
            val: null,
            label: "METHOD",
            type: 'select',
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
          is_caching: {
            cond: "none",
            val: null,
            label: "IS CACHING",
            type: 'select',
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
          layout: {
            cond: "none",
            val: null,
            label: "LAYOUT",
            type: 'select',
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
          is_public: {
            cond: "none",
            val: null,
            label: "IS PUBLIC",
            type: 'select',
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
        },
        event: {
          ['@show-filter.window']() {
            this.showModal();
          },
        },
        getValues() {
          let values = _.cloneDeep(this.field);
          values = _.mapValues(values, function(o) { return {cond:o.cond,val:o.val}; });
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
        }
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
});
