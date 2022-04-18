document.addEventListener('alpine:init', () => {
    Alpine.data('xtable', () => ({
        data: null,
        xinit() {
            const mainLoader = Alpine.store('loader');
            mainLoader.main = true;

            // let _data = _.cloneDeep(this.data);
            let _data = null;

            // let formData = new FormData();
            // formData = objectToFormData(_data, formData);
            let csrf = getCSRFToken();
            fetchWithTimeout(
                `/core-admin/manage-script/controller/reload`, {
                method: 'POST',
                redirect: 'error',
                headers: {
                    Accept: 'application/json',
                    'Content-type': 'application/json',
                    [csrf.name]: csrf.val
                },
                body: JSON.stringify(_data)
            },
                10000
            )
                .then((response) => response.json())
                .then((json) => {
                    // console.log(json);
                    if (json.ret) {
                        mainLoader.main = false;
                        var retAjax = json.data;
                        // console.log(retAjax);
                        this.setData(retAjax);
                    } else {
                        mainLoader.main = false;
                    }
                })
                .catch((error) => {
                    console.log(error);
                    mainLoader.main = false;
                    let notifStore = Alpine.store('notif_modal');
                    notifStore.show(`Data User`, `Failed <b>Select Data</b>`, "", "failed", `OK`, () => {
                        notifStore.hide();
                    });
                });
        },
        setData(data) {
            this.data = data;
        },
        sorting: {
            id: {
                order: "none",
                priority: null
            },
            name: {
                order: "none",
                priority: null
            },
            module: {
                order: "none",
                priority: null
            },
            status: {
                order: "none",
                priority: null
            },
            factory: {
                order: "none",
                priority: null
            },
        },
        active_sort_field: null,
        bind: {
            col_header: {
                ['@click']() {
                    this.changeSorting(this.$el.dataset.field);
                }
            }
        },
        changeSorting(field) {
            let curSort = this.sorting[field].order;
            if (field !== this.active_sort_field && this.active_sort_field !== null) {
                this.sorting[this.active_sort_field].order = "none";
            }

            if (curSort === "none") {
                this.sorting[field].order = "asc";
                this.active_sort_field = field;
            } else if (curSort === "asc") {
                this.sorting[field].order = "desc";
                this.active_sort_field = field;
            } else if (curSort === "desc") {
                this.sorting[field].order = "none";
                this.active_sort_field = null;
            }
        },
        refreshData() {
            this.init();
        },
        event: {
            ['@refresh-data.window']() {
                this.refreshData();
            },
            ['@get-controllerbyidx.window']() {
                // console.log(this.$event.detail);
                console.log("event 2 data table");
                if (this.data[this.$event.detail] !== undefined) {
                    active_controller_edit = _.cloneDeep(this.data[this.$event.detail]);
                } else {
                    active_controller_edit = null;
                }

            }
        },
        tes() {
            console.log("tes");
        }
    }));

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
            module: {
                order: "none",
                priority: null
            },
            status: {
                order: "none",
                priority: null
            },
            factory: {
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
                    const controllerStore = Alpine.store('controller');
                    controllerStore.checkAll(checked);
                    // console.log(this.$el.checked);
                },
            },
            /* -------------------------- Row Checkbox Binding ------------------------- */
            row_checkbox: {
                ['@click']() {
                    let dataset = this.$el.dataset;
                    let checked = this.$el.checked;
                    const controllerStore = Alpine.store('controller');
                    controllerStore.changeCheck(dataset.idx, checked);
                    // console.log({ dataset, checked });
                },
            },
            /* --------------------------- Table Body Binding -------------------------- */
            table_body: {
                ['@remove-all-module.window']() {
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
            if (field !== this.active_sort_field && this.active_sort_field !== null) {
                this.sorting[this.active_sort_field].order = "none";
            }
            // console.log([field,this.active_sort_field,curSort]);
            if (curSort === "none") {
                this.sorting[field].order = "asc";
                this.active_sort_field = field;
            } else if (curSort === "asc") {
                this.sorting[field].order = "desc";
                this.active_sort_field = field;
            } else if (curSort === "desc") {
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
            let btn = "Activate Controller";
            if (!this.$el.checked) {
                act = "<b class='text-red-700'>deactivate</b>";
                btn = "Deactivate Controller";
            }
            this.$el.checked = old_val;
            const confirmStore = Alpine.store('confirm_modal');
            const moduleStore = Alpine.store('controller');
            let module = moduleStore.getCurrentDataByIndex(idx);
            confirmStore.show(
                `${btn}`, `Are you sure you want to ${act} <b>Controller ${module.id} - ${module.name}</b> ?`, "", "info", `${btn}`,
                () => {
                    this.$el.checked = !old_val;
                    confirmStore.hide();
                }
            );
        },
        removeRow(idx) {
            // console.log(idx, this.$el.checked);
            let act = "<b class='text-red-700'>remove</b>";
            let btn = "Remove Controller";
            const confirmStore = Alpine.store('confirm_modal');
            const controllerStore = Alpine.store('controller');
            let controller = controllerStore.getCurrentDataByIndex(idx);
            confirmStore.show(
                `${btn}`, `Are you sure you want to ${act} <b>Controller ${controller.id} - ${controller.name}</b> ?`, "", "remove", `${btn}`,
                () => {
                    confirmStore.hide();
                    removeController(controller);
                }
            );
        },
        removeAllRow(idx) {
            // console.log(idx, this.$el.checked);
            let act = "<b class='text-red-700'>remove</b>";
            let btn = "Remove Selected Controller";

            const controllerStore = Alpine.store('controller');
            let modules = controllerStore.getSelectedController();
            // console.table(modules);
            if (modules.length > 0) {
                const confirmStore = Alpine.store('confirm_modal');
                confirmStore.show(
                    `${btn}`, `Are you sure you want to ${act} <b>Selected Controller</b> ?`, "", "remove", `${btn}`,
                    () => {
                        confirmStore.hide();
                        removeMultiModule(modules.map((v, k) => { return v.id }));
                    }
                );
            }
        }
    }));

    Alpine.data('add_controller', () => ({
        show: false,
        notif: false,
        name_msg: "",
        data: {
            name: null,
            module: null,
            status: 0,
            factory: null
        },
        module_opt: [],
        xinit() {
            this.module_opt = list_module;
        },
        event_add: {
            ['@show-addmodal.window']() {
                this.showModal();
            },
        },
        bind: {
            name: {
                input: {
                    [':class']() {
                        return {
                            'focus:ring-bluegray-500 focus:border-bluegray-500 border-gray-300': this.name_msg === "",
                            'focus:ring-red-500 focus:border-red-500 border-red-300': this.name_msg !== ""
                        };
                    }
                }
            },

        },
        showModal() {
            this.show = true;
        },
        hideModal() {
            this.show = false;
        },
        shoNotif() {
            this.notif = true;
        },
        hideNotif() {
            this.notif = false;
        },
        changeStatus() {
            if (this.data.status === 1) {
                this.data.status = 0;
            } else {
                this.data.status = 1;
            }
        },
        clearFormAdd() {
            this.data.name = null,
                this.data.module = null,
                this.data.status = 0,
                this.data.factory = null
        },
        refresh() {
            console.log("refresh");
            this.$dispatch('refresh-data', this.$event.detail);
        },
        submit() {
            let _data = _.cloneDeep(this.data);
            // console.log(_data);
            let csrf = getCSRFToken();
            this.name_msg = "";

            if (this.data.name !== null && this.data.name !== undefined && this.data.name !== "") {
                const mainLoader = Alpine.store('loader');
                mainLoader.main = true;
                fetchWithTimeout(
                    `/core-admin/manage-script/controller/add_new`, {
                    method: 'POST',
                    redirect: 'error',
                    headers: {
                        Accept: 'application/json',
                        'Content-type': 'application/json',
                        [csrf.name]: csrf.val
                    },
                    body: JSON.stringify(_data)
                },
                    10000
                )
                    .then(response => response.json())
                    .then(json => {
                        console.log('connected', json);
                        if (json.ret) {
                            console.log("success")
                            let notifStore = Alpine.store('notif_modal');
                            notifStore.show(`Create Controller`, `Success <b>Create Controller</b>`, "", "success", `OK`, () => {
                                notifStore.hide();
                                // this.refresh();
                                location.reload();
                                //  this.$dispatch('refresh-data', this.$event.detail);
                            });
                            this.clearFormAdd();
                            this.hideModal();
                            mainLoader.main = false;
                        } else {
                            console.log("unsuccess")
                            if (json.ret == false) {
                                this.name_msg = json.msg;
                                this.notif = true;
                            }
                            mainLoader.main = false;
                        }

                    })
                    .catch(error => {
                        console.error('Error:', error.message);
                        mainLoader.main = false;
                        // let notifStore = Alpine.store('notif_modal');
                        // notifStore.show(`Add User`, `Failed <b>Add Data</b>`, "", "failed", `OK`, () => {
                        //   notifStore.hide();
                        // });
                    });

            } else {
                this.name_msg = "Action Name cannot be null";
                this.notif = true;
            }

        },

    }));

    Alpine.data('edit_controller', () => ({
        show: false,
        data: [],
        module_opt: [],
        initx() {
            console.log("ini init edit controller");
            this.module_opt = list_module;
        },
        event_edit: {
            ['@show-editmodal.window']() {
                this.$dispatch('get-controllerbyidx.window', this.$event.detail);
                console.log(active_controller_edit);
                this.showModaledit();
            }
        },
        initFormedit() {
            // this.data.username = active_user_edit.username;
            // this.data.full_name = active_user_edit.full_name;
            // this.data.email = active_user_edit.email;
            // this.data.mobile_no = active_user_edit.mobile_no;
            // this.data.employ_nik = active_user_edit.employ_nik;
            // this.data.spv_nik = active_user_edit.spv_nik;
            // this.data.redirect_route = active_user_edit.redirect_route;
            // this.data.redirect_param = active_user_edit.redirect_param;
            // this.data.redirect_url = active_user_edit.redirect_url;
            // this.data.telegram_id = active_user_edit.telegram_id;
            // this.data.status = active_user_edit.status;
            // this.data.ldap = active_user_edit.ldap;

            // this.data = _.merge(this.data, active_user_edit);
            console.log(active_controller_edit);
            this.data = active_controller_edit;

        },
        clearFormedit() {
            this.data.name = null;
            this.data.module = null;
            this.data.factory = null;
            active_user_edit = null;
        },
        showModaledit() {
            this.initFormedit();
            this.show = true;
        },
        hideModal() {
            this.show = false;
            this.clearFormedit();
        },
        submit() {
            const mainLoader = Alpine.store('loader');
            mainLoader.main = true;

            let _data = _.cloneDeep(this.data);

            // let formData = new FormData();
            // formData = objectToFormData(_data, formData);
            let csrf = getCSRFToken();
            fetchWithTimeout(
                `/core-admin/manage-user/process/${_data.id}/edit`, {
                method: 'POST',
                redirect: 'error',
                headers: {
                    Accept: 'application/json',
                    'Content-type': 'application/json',
                    [csrf.name]: csrf.val
                },
                body: JSON.stringify(_data)
            },
                10000
            )
                .then((response) => response.json())
                .then((json) => {
                    // console.log(json);
                    if (json.ret) {
                        mainLoader.main = false;
                        if (json.process) {
                            console.log(_data);
                            let notifStore = Alpine.store('notif_modal');
                            notifStore.show(`Edit User`, `Success <b>Edit Data</b>`, json.msg, "success", `OK`, () => {
                                notifStore.hide();
                                // this.hideModal();
                                location.reload();
                            });
                        } else {
                            let notifStore = Alpine.store('notif_modal');
                            notifStore.show(`Edit User`, `Failed <b>Edit Data</b>`, json.msg, "failed", `OK`, () => {
                                notifStore.hide();
                            });
                        }
                    } else {
                        mainLoader.main = false;
                    }
                })
                .catch((error) => {
                    console.error(error.message);
                    mainLoader.main = false;
                    let notifStore = Alpine.store('notif_modal');
                    notifStore.show(`Edit User`, `Failed <b>Edit Data</b>`, "", "failed", `OK`, () => {
                        notifStore.hide();
                    });
                });
        }
    }));

    Alpine.data('filter', () => ({
        /* ------------------------------ Modal Toggler ------------------------------ */
        show: false,
        field: {
            id: {
                cond: "none",
                val: null,
                label: "CONTROLLER ID",
                type: 'text'
            },
            name: {
                cond: "none",
                val: null,
                label: "ACTION NAME",
                type: 'text'
            },
            module: {
                cond: "none",
                val: null,
                label: "MODULE",
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
            factory: {
                cond: "none",
                val: null,
                label: "FACTORY",
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
            values = _.mapValues(values, function (o) { return { cond: o.cond, val: o.val }; });
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
                    tomsel_dom["filtercond-" + key].value = (this.field[key].cond !== null) ? this.field[key].cond : "none";
                    let evt = document.createEvent('HTMLEvents');
                    evt.initEvent('change', false, true);
                    tomsel_dom["filtercond-" + key].dispatchEvent(evt);
                }
                if (tomsel["filterval-" + key] !== undefined) {
                    tomsel_dom["filterval-" + key].value = (this.field[key].val !== null) ? this.field[key].val : "none";
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
                    pagingStore.data.current_page = pagingStore.data.current_page + 1;
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
                    } else if (pagingStore.data.current_page < 1) {
                        pagingStore.data.current_page = 1;
                    }
                    filterData();
                }
            }
        }
    }));

    Alpine.data('create', () => ({
        /* ------------------------------ Modal Toggler ------------------------------ */
        show: false,
        title: "Create New Controller",
        data: {
            id: null,
            name: null,
            module: null,
            module_id: null,
            status: false,
            factory: null
        },
        name_msg: "",
        name_msg_module: "",
        name_valid: false,
        module_opt: [],
        init() {
            this.module_opt = list_module;
        },
        bind: {
            self: {
                ['@show-create.window']() {
                    this.showModal();
                },
                ['@show-edit.window']() {
                    let idx = this.$event.detail;
                    this.showEdit(idx);
                },
                ['@create-name-exist.window']() {
                    console.log(this.$event);
                    let msg = this.$event.detail;
                    this.name_msg = msg;
                },
                ['@success-create.window']() {
                    this.cancelCreate();
                    reloadData();
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
                        if (this.data.status === true) {
                            this.data.status = 1;
                        } else if (this.data.status === false) {
                            this.data.status = 0;
                        } else {
                            this.data.status = 0;
                        }
                        // console.log(this.data.status);
                    },
                    [':class']() {
                        return {
                            'bg-gray-200': !this.data.status,
                            'bg-green-600 focus:ring-green-500': this.data.status
                        };
                    }
                },
                btn_span: {
                    [':class']() {
                        return {
                            'translate-x-0': !this.data.status,
                            'translate-x-5': this.data.status
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
            this.title = "Create New Controller";
            this.name_msg = "";
            this.name_msg_module = "";
            this.data = {
                id: null,
                name: null,
                status: false,
                factory: null
            };
        },
        submitCreate() {
            this.name_msg = "";
            let current_data = _.cloneDeep(this.data);
            if (current_data.factory === null) {
                current_data.factory = "Core_Factory_MainFactory";
            }
            if (current_data.status === false) {
                current_data.status = 0;
            }
            // console.log(current_data);
            // this.hideModal();
            if (current_data.name === "" || current_data.name === null) {
                this.name_msg = "Controller name can not be null";
            } else if (current_data.module_id === "--" || current_data.module_id === null) {
                this.name_msg_module = "Module can not be null";
            } else {
                if (current_data.id !== null) {
                    updateController(current_data);
                } else {
                    current_data = _.pickBy(current_data, (val, idx) => {
                        return idx !== "id";
                    });
                    // console.log(current_data);
                    addController(current_data);
                }
            }
        },
        cancelCreate() {
            if (this.data.id !== null) {
                this.emptyForm();
            }
            this.name_msg = "";
            this.name_msg_module = "";
            this.emptyForm();
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
            const module_dataStore = Alpine.store('controller');
            let _data = _.cloneDeep(module_dataStore.getCurrentDataByIndex(idx));
            // console.table(_data);
            this.title = "Edit Controller - " + _data.id;
            this.data = {
                id: _data.id,
                name: _data.name,
                module: _data.module,
                module_id: _data.module_id,
                status: parseInt(_data.status) === 1,
                factory: _data.factory
            };
            console.log(this.data);
            this.showModal();
        },
        showModal() {
            this.show = true;
        },
        hideModal() {
            this.show = false;
        }
    }));

})

document.addEventListener('alpine:initialized', () => {

});