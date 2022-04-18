document.addEventListener('alpine:init', () => {
    Alpine.data('table', () => ({
        sorting: {
            id: {
                order: "none",
                priority: null
            },
            code: {
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
            redirect_route: {
                order: "none",
                priority: null
            },
            redirect_param: {
                order: "none",
                priority: null
            },
            redirect_query: {
                order: "none",
                priority: null
            },
            redirect_url: {
                order: "none",
                priority: null
            },
        },
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
            /* ------------------------ Button Job Remove Binding ------------------------ */
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
                const jobStore = Alpine.store('job');
                jobStore.checkAll(checked);
                // console.log(this.$el.checked);
              },
            },
            /* -------------------------- Row Checkbox Binding ------------------------- */
            row_checkbox: {
              ['@click']() {
                let dataset = this.$el.dataset;
                let checked = this.$el.checked;
                const jobStore = Alpine.store('job');
                jobStore.changeCheck(dataset.idx,checked);
                // console.log({ dataset, checked });
              },
            },
            /* --------------------------- Table Body Binding -------------------------- */
            table_body: {
              ['@remove-all-job.window']() {
                // console.log("remove all");
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
          let btn = "Activate Job";
          if (!this.$el.checked) {
            act = "<b class='text-red-700'>deactivate</b>";
            btn = "Deactivate Job";
          }
          this.$el.checked = old_val;
          const confirmStore = Alpine.store('confirm_modal');
          const jobStore = Alpine.store('job');
          let job = jobStore.getCurrentDataByIndex(idx);
          confirmStore.show(
            `${btn}`, `Are you sure you want to ${act} <b>Job ${job.id} - ${job.name}</b> ?`, "", "info", `${btn}`,
            () => {
              // this.$el.checked = !old_val;
              confirmStore.hide();
              changeStatus(job,this.$el, old_val);
            }
          );
        },
        removeRow(idx) {
          // console.log(idx, this.$el.checked);
          let act = "<b class='text-red-700'>remove</b>";
          let btn = "Remove Job";
          const confirmStore = Alpine.store('confirm_modal');
          const jobStore = Alpine.store('job');
          let job = jobStore.getCurrentDataByIndex(idx);
          confirmStore.show(
            `${btn}`, `Are you sure you want to ${act} <b>Job ${job.id} - ${job.name}</b> ?`, "", "remove", `${btn}`,
            () => {
              confirmStore.hide();
              removeJob(job);
            }
          );
        },
        removeAllRow(idx) {
          // console.log(idx, this.$el.checked);
          let act = "<b class='text-red-700'>remove</b>";
          let btn = "Remove Selected Job";
    
          const jobStore = Alpine.store('job');
          let jobs = jobStore.getSelectedJob();
          // console.table(jobs);
          if (jobs.length > 0) {
            const confirmStore = Alpine.store('confirm_modal');
            confirmStore.show(
              `${btn}`, `Are you sure you want to ${act} <b>Selected Job</b> ?`, "", "remove", `${btn}`,
              () => {
                confirmStore.hide();
                removeMultiJob(jobs.map((v,k)=>{return v.id}));
              }
            );
          }
        }
    }));

    Alpine.data('create', () => ({
      /* ------------------------------ Modal Toggler ------------------------------ */
      show: false,
      title: "Create New Job",
      data: {
        id: null,
        code: null,
        name: null,
        parent: null,
        status: false,
        redirect_route: null,
        redirect_param: null,
        redirect_query: null,
        redirect_url: null,
      },
      code_msg: "",
      name_msg: "",
      name_valid:false,
      code_valid:false,
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
          ['@create-code-exist.window']() {
            console.log(this.$event);
            let msg = this.$event.detail;
            this.code_msg = msg;
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
        code: {
          input: {
            [':class']() {
              return {
                'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.code_msg === "",
                'focus:ring-red-500 focus:border-red-500 border-red-300': this.code_msg !== ""
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
        this.title = "Create New Job";
        this.name_msg = "";
        this.code_msg = "";
        this.data = {
            id: null,
            code: null,
            name: null,
            parent: null,
            status: false,
            redirect_route: null,
            redirect_param: null,
            redirect_query: null,
            redirect_url: null,
        };
      },
      submitCreate() {
        this.name_msg = "";
        this.code_msg = "";
        let current_data = _.cloneDeep(this.data);
        // console.log(current_data);
        this.hideModal();
        if (current_data.name === "" || current_data.name === null && current_data.code === "" || current_data.code === null) {
          this.name_msg = "Job name can not be null";
          this.code_msg = "Job code can not be null";
        } else {
          if (current_data.id !== null) {
            // console.log(current_data);
            updateJob(current_data);
          } else {
            current_data = _.pickBy(current_data, (val, idx) => {
              return idx !== "id";
            });
            // console.log(current_data);
            addJob(current_data);
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
        const job_dataStore = Alpine.store('job');
        let _data = _.cloneDeep(job_dataStore.getCurrentDataByIndex(idx));
        console.table(_data);
        this.title = "Edit Job - "+_data.id;
        this.data = {
          id: _data.id,
          code: _data.code,
          name: _data.name,
          parent: _data.parent,
          status: _.toInteger(_data.status)===1,
          redirect_route: _data.redirect_route,
          redirect_param: _data.redirect_param,
          redirect_query: _data.redirect_query,
          redirect_url: _data.redirect_url,
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
            label: "JOB ID",
            type: 'text'
          },
          code: {
            cond: "none",
            val: null,
            label: "JOB CODE",
            type: 'text'
          },
          name: {
            cond: "none",
            val: null,
            label: "JOB NAME",
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
          redirect_route: {
            cond: "none",
            val: null,
            label: "REDIRECT ROUTE",
            type: 'text'
          },
          redirect_param: {
            cond: "none",
            val: null,
            label: "REDIRECT PARAM",
            type: 'text'
          },
          redirect_query: {
            cond: "none",
            val: null,
            label: "REDIRECT QUERY",
            type: 'text'
          },
          redirect_url: {
            cond: "none",
            val: null,
            label: "REDIRECT URL",
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


})