document.addEventListener('alpine:init', () => {
    Alpine.data('table', () => ({
      sorting: {
        id: {
          order: "none",
          priority: null
        },
        user: {
          order: "none",
          priority: null
        },
        pos: {
          order: "none",
          priority: null
        },
        start_dt: {
          order: "none",
          priority: null
        },
        end_dt: {
          order: "none",
          priority: null
        },
        status: {
          order: "none",
          priority: null
        },
        main: {
          order: "none",
          priority: null
        }
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
        /* ------------------------ Column Main Binding ------------------------ */
        col_main: {
          ['@click']() {
            // console.log(this.$el.dataset.field);
            this.changeStatus(this.$el.dataset.idx);
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
            const jobposStore = Alpine.store('jobpos');
            jobposStore.checkAll(checked);
            // console.log(this.$el.checked);
          },
        },
        /* -------------------------- Row Checkbox Binding ------------------------- */
        row_checkbox: {
          ['@click']() {
            let dataset = this.$el.dataset;
            let checked = this.$el.checked;
            const jobposStore = Alpine.store('jobpos');
            jobposStore.changeCheck(dataset.idx,checked);
            // console.log({ dataset, checked });
          },
        },
        /* --------------------------- Table Body Binding -------------------------- */
        table_body: {
          ['@remove-all-jobpos.window']() {
            console.log("remove all");
            this.removeAllRow();
          },
          ['@download-data.window']() {
            console.log("download all");
            filterData(true);
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
        let btn = "Activate Jobpos";
        if (!this.$el.checked) {
          act = "<b class='text-red-700'>deactivate</b>";
          btn = "Deactivate Jobpos";
        }
        this.$el.checked = old_val;
        const confirmStore = Alpine.store('confirm_modal');
        const jobposStore = Alpine.store('jobpos');
        let jobpos = jobposStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Jobpos ${jobpos.id} - ${jobpos.user}</b> ?`, "", "info", `${btn}`,
          () => {
            // this.$el.checked = !old_val;
            confirmStore.hide();
            changeStatus(jobpos,this.$el, old_val);
          }
        );
      },
      removeRow(idx) {
        // console.log(idx, this.$el.checked);
        let act = "<b class='text-red-700'>remove</b>";
        let btn = "Remove Jobpos";
        const confirmStore = Alpine.store('confirm_modal');
        const jobposStore = Alpine.store('jobpos');
        let jobpos = jobposStore.getCurrentDataByIndex(idx);
        confirmStore.show(
          `${btn}`, `Are you sure you want to ${act} <b>Jobpos ${jobpos.id} - ${jobpos.user}</b> ?`, "", "remove", `${btn}`,
          () => {
            confirmStore.hide();
            removeJobpos(jobpos);
          }
        );
      },
      removeAllRow(idx) {
        // console.log(idx, this.$el.checked);
        let act = "<b class='text-red-700'>remove</b>";
        let btn = "Remove Selected Jobpos";
  
        const jobposStore = Alpine.store('jobpos');
        let jobposs = jobposStore.getSelectedJobpos();
        // console.table(jobposs);
        if (jobposs.length > 0) {
          const confirmStore = Alpine.store('confirm_modal');
          confirmStore.show(
            `${btn}`, `Are you sure you want to ${act} <b>Selected Jobpos</b> ?`, "", "remove", `${btn}`,
            () => {
              confirmStore.hide();
              removeMultiJobpos(jobposs.map((v,k)=>{return v.id}));
            }
          );
        }
      }
    }));
  
    Alpine.data('create', () => ({
      /* ------------------------------ Modal Toggler ------------------------------ */
      show: false,
      title: "Create New Jobpos",
      data: {
        id: null,
        user: null,
        pos: null,
        start_dt: null,
        end_dt: null,
        status: false,
        main: null
      },
      user_msg: "",
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
            this.user_msg = msg;
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
        main: {
          label1: {
            [':class']() {
              return {
                'font-medium text-gray-900': !this.data.main,
                'text-gray-500': this.data.main
              };
            }
          },
          label2: {
            [':class']() {
              return {
                'font-medium text-green-900': this.data.main,
                'text-gray-500': !this.data.main
              };
            }
          },
          btn: {
            ['@click']() {
              this.data.main = !this.data.main;
            },
            [':class']() {
              return {
                'bg-gray-200': !this.data.main,
                'bg-green-600 focus:ring-green-500': this.data.main
              };
            }
          },
          btn_span:{
            [':class']() {
              return {
                'translate-x-0': !this.data.main,
                'translate-x-5': this.data.main
              };
            }
          }
        },
        user: {
          input: {
            [':class']() {
              return {
                'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.user_msg === "",
                'focus:ring-red-500 focus:border-red-500 border-red-300': this.user_msg !== ""
              };
            }
          }
        }
      },
      emptyForm() {
        this.title = "Create New Jobpos";
        this.name_msg = "";
        this.data = {
          id: null,
          user: null,
          pos: null,
          start_dt: null,
          end_dt: null,
          status: false,
          main: false
        };
      },
      submitCreate() {
        this.name_msg = "";
        let current_data = _.cloneDeep(this.data);
        // console.log(current_data);
        this.hideModal();
        if (current_data.name === "" || current_data.name === null) {
          this.name_msg = "Jobpos name can not be null";
        } else {
          if (current_data.id !== null) {
            updateJobpos(current_data);
          } else {
            current_data = _.pickBy(current_data, (val, idx) => {
              return idx !== "id";
            });
            // console.log(current_data);
            addJobpos(current_data);
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
        const jobpos_dataStore = Alpine.store('jobpos');
        let _data = _.cloneDeep(jobpos_dataStore.getCurrentDataByIndex(idx));
        console.table(_data);
        this.title = "Edit Jobpos - "+_data.id;
        this.data = {
          id: _data.id,
          user: _data.user,
          pos: _data.pos,
          start_dt: _data.start_dt,
          end_dt: _data.end_dt,
          status: _.toInteger(_data.status)===1,
          main: _.toInteger(_data.main)===1,
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
          label: "POS ID",
          type: 'text'
        },
        user: {
          cond: "none",
          val: null,
          label: "USER",
          type: 'text'
        },
        pos: {
          cond: "none",
          val: null,
          label: "POS",
          type: 'text'
        },
        start_dt: {
          cond: "none",
          val: null,
          label: "START DATE",
          type: 'text'
        },
        end_dt: {
          cond: "none",
          val: null,
          label: "END DATE",
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
        main: {
          cond: "none",
          val: null,
          label: "MAIN",
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
            reloadData(0);
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
        url: "/core-admin/manage-script/jobpos/upload",
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