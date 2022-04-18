document.addEventListener('alpine:init', () => {
    Alpine.data('table', () => ({
        id_user: null,
        _list_user : null,
        _uid: null,
        sorting: {
            id: {
                order: "none",
                priority: null
            },
            route: {
                order: "none",
                priority: null
            },
            layout: {
                order: "none",
                priority: null
            },
            status: {
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
                    const layoutStore = Alpine.store('route_user');
                    layoutStore.checkAll(checked);
                    // console.log(this.$el.checked);
                },
            },
            /* -------------------------- Row Checkbox Binding ------------------------- */
            row_checkbox: {
                ['@click']() {
                    let dataset = this.$el.dataset;
                    let checked = this.$el.checked;
                    const roleStore = Alpine.store('route_user');
                    roleStore.changeCheck(dataset.idx, checked);
                    // console.log({ dataset, checked });
                },
            },
            /* --------------------------- Table Body Binding -------------------------- */
            table_body: {
                ['@remove-all-role.window']() {
                    console.log("remove all");
                    this.removeAllRow();
                },
            }
        },
        init() {
            this._list_user = list_user;
            this._uid = uid;
            if (this._uid !== null) {
                reloadData(this._uid);
                console.log("call uid: " + this._uid);
            }
        },
        SelectDataMenu() {
            // console.log(this.id_user);
            reloadData(this.id_user);
            _id_user = this.id_user;
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
            let btn = "Activate Route";
            if (!this.$el.checked) {
                act = "<b class='text-red-700'>deactivate</b>";
                btn = "Deactivate Route";
            }
            this.$el.checked = old_val;
            const confirmStore = Alpine.store('confirm_modal');
            const roleStore = Alpine.store('route_user');
            let role = roleStore.getCurrentDataByIndex(idx);
            confirmStore.show(
                `${btn}`, `Are you sure you want to ${act} <b>Route ${role.id} - ${role.route}</b> ?`, "", "info", `${btn}`,
                () => {
                    this.$el.checked = !old_val;
                    confirmStore.hide();
                    if (this.$el.checked === true) {
                        role.status = 1;
                    } else if (this.$el.checked === false) {
                        role.status = 0;
                    }
                    console.log(role);
                    updatestatusRoute(role);
                }
            );
        },
        removeRow(idx) {
            // console.log(idx, this.$el.checked);
            let act = "<b class='text-red-700'>remove</b>";
            let btn = "Remove Route";
            const confirmStore = Alpine.store('confirm_modal');
            const roleStore = Alpine.store('route_user');
            let role = roleStore.getCurrentDataByIndex(idx);
            confirmStore.show(
                `${btn}`, `Are you sure you want to ${act} <b>Route ${role.id} - ${role.Route}</b> ?`, "", "remove", `${btn}`,
                () => {
                    confirmStore.hide();
                    removeRoute(role);
                }
            );
        },
        removeAllRow(idx) {
            // console.log(idx, this.$el.checked);
            let act = "<b class='text-red-700'>remove</b>";
            let btn = "Remove Selected Route";

            const roleStore = Alpine.store('route_user');
            let role = roleStore.getSelectedUser();
            // console.table(modules);
            if (role.length > 0) {
                const confirmStore = Alpine.store('confirm_modal');
                confirmStore.show(
                    `${btn}`, `Are you sure you want to ${act} <b>Selected Route</b> ?`, "", "remove", `${btn}`,
                    () => {
                        confirmStore.hide();
                        removeMultiRoute(role.map((v, k) => { return v.id }));
                    }
                );
            }
        }
    }));

    Alpine.data('filter', () => ({
        /* ------------------------------ Modal Toggler ------------------------------ */
        show: false,
        field: {
            id: {
                cond: "none",
                val: null,
                label: "ID",
                type: 'text'
            },
            route: {
                cond: "none",
                val: null,
                label: "ROUTE",
                type: 'text'
            },
            layout: {
                cond: "none",
                val: null,
                label: "LAYOUT",
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
        title: "Set New Route",
        data: {
            id: null,
            layout: null,
            layout_id: null,
            route: null,
            route_id: null,
            status: null,
            user_id: null,
        },
        route_msg: "",
        layout_msg: "",
        name_valid: false,
        module_opt: [],
        route_opt: [],
        init() {
            this.module_opt = list_module;
            this.route_opt = list_route;
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
                        console.log(this.data.status);
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
            this.title = "Set New Route";
            this.route_msg = "";
            this.layout_msg = "";

            this.data = {
                id: null,
                layout: null,
                layout_id: null,
                route: null,
                route_id: null,
                status: null,
                user_id: null,
            };
        },
        submitCreate() {
            this.route_msg = "";
            this.layout_msg = "";

            let current_data = _.cloneDeep(this.data);

            if (current_data.status === false) {
                current_data.status = 0;
            } else if (current_data.status === true) {
                current_data.status = 1;
            } else if (current_data.status === null) {
                current_data.status = 0;
            }
            // console.log(current_data);
            // this.hideModal();
            if (current_data.route_id === "" || current_data.route_id === null) {
                this.route_msg = "Select Route !!!";
            } else if (current_data.layout_id === "" || current_data.layout_id === null) {
                this.layout_msg = "Select Layout !!!";
            } else {
                if (current_data.id !== null) {
                    updateRoute(current_data);
                    this.cancelCreate();
                } else {
                    current_data = _.pickBy(current_data, (val, idx) => {
                        return idx !== "id";
                    });
                    current_data.user_id = _id_user;
                    // console.log(current_data);
                    setNewRoute(current_data);
                    this.cancelCreate();
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
            const role_dataStore = Alpine.store('route_user');
            let _data = _.cloneDeep(role_dataStore.getCurrentDataByIndex(idx));
            // console.table(_data);
            this.title = "Edit Route - " + _data.id;
            this.data = {
                id: _data.id,
                layout: _data.layout,
                layout_id: _data.layout_id,
                route: _data.route,
                route_id: _data.route_id,
                user_id: _data.user_id,
                status: parseInt(_data.status),
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