document.addEventListener('alpine:init', () => {
    Alpine.data('table', () => ({
        sorting: {
            id: {
                order: "none",
                priority: null
            },
            module: {
                order: "none",
                priority: null
            },
            layout: {
                order: "none",
                priority: null
            },
            title: {
                order: "none",
                priority: null
            },
            route: {
                order: "none",
                priority: null
            },
            param: {
                order: "none",
                priority: null
            },
            query: {
                order: "none",
                priority: null
            },
            url: {
                order: "none",
                priority: null
            },
            icon: {
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
            desc: {
                order: "none",
                priority: null
            },
            priority: {
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
                    const menuStore = Alpine.store('menu');
                    menuStore.checkAll(checked);
                    // console.log(this.$el.checked);
                },
            },
            /* -------------------------- Row Checkbox Binding ------------------------- */
            row_checkbox: {
                ['@click']() {
                    let dataset = this.$el.dataset;
                    let checked = this.$el.checked;
                    const menuStore = Alpine.store('menu');
                    menuStore.changeCheck(dataset.idx, checked);
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
            let btn = "Activate Menu";
            if (!this.$el.checked) {
                act = "<b class='text-red-700'>deactivate</b>";
                btn = "Deactivate Menu";
            }
            this.$el.checked = old_val;
            const confirmStore = Alpine.store('confirm_modal');
            const menuStore = Alpine.store('menu');
            let menu = menuStore.getCurrentDataByIndex(idx);
            confirmStore.show(
                `${btn}`, `Are you sure you want to ${act} <b>Menu ${menu.id} - ${menu.title}</b> ?`, "", "info", `${btn}`,
                () => {
                    this.$el.checked = !old_val;
                    confirmStore.hide();
                    if (this.$el.checked === true) {
                        menu.status = 1;
                    } else if (this.$el.checked === false) {
                        menu.status = 0;
                    }
                    console.log(menu);
                    updatestatusMenu(menu);
                }
            );
        },
        removeRow(idx) {
            // console.log(idx, this.$el.checked);
            let act = "<b class='text-red-700'>remove</b>";
            let btn = "Remove Layout";
            const confirmStore = Alpine.store('confirm_modal');
            const menuStore = Alpine.store('menu');
            let menu = menuStore.getCurrentDataByIndex(idx);
            confirmStore.show(
                `${btn}`, `Are you sure you want to ${act} <b>Menu ${menu.id} - ${menu.title}</b> ?`, "", "remove", `${btn}`,
                () => {
                    confirmStore.hide();
                    removeMenu(menu);
                }
            );
        },
        removeAllRow(idx) {
            // console.log(idx, this.$el.checked);
            let act = "<b class='text-red-700'>remove</b>";
            let btn = "Remove Selected Menu";

            const menuStore = Alpine.store('menu');
            let modules = menuStore.getSelectedMenu();
            // console.table(modules);
            if (modules.length > 0) {
                const confirmStore = Alpine.store('confirm_modal');
                confirmStore.show(
                    `${btn}`, `Are you sure you want to ${act} <b>Selected Menu</b> ?`, "", "remove", `${btn}`,
                    () => {
                        confirmStore.hide();
                        removeMultiMenu(modules.map((v, k) => { return v.id }));
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
                label: "MENU ID",
                type: 'text'
            },
            title: {
                cond: "none",
                val: null,
                label: "TITLE",
                type: 'text'
            },
            module: {
                cond: "none",
                val: null,
                label: "MODULE",
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
        title: "Create New Menu",
        data: {
            id: null,
            title: null,
            module: null,
            module_id: null,
            layout: null,
            layout_id: null,
            route: null,
            route_id: null,
            param: null,
            query: null,
            url: null,
            icon: null,
            status: null,
            parent: null,
            parent_id: null,
            desc: null,
            priority: null
        },
        name_msg: "",
        name_msg_module: "",
        layout_msg: "",
        route_msg: "",
        priority_msg: "",
        param_msg: "",
        query_msg: "",
        name_valid: false,
        module_opt: [],
        layout_optx: [],
        route_opty: [],
        parent_opt: [],
        init() {
            this.module_opt = list_module;
            this.layout_optx = list_layout;
            this.route_opty = list_route;
            this.parent_opt = list_parent;
        },
        selected(idx, idx2) {
            if (idx === idx2) {
                var _selected = "selected";
            } else {
                var _selected = "";
            }
            return _selected;
            // console.log(_selected);
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
                    this.parent_opt.push(_.cloneDeep(this.$event.detail));
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
            this.title = "Create New Layout";
            this.name_msg = "";
            this.name_msg_module = "";
            this.layout_msg = "";
            this.route_msg = "";
            this.priority_msg = "";
            this.param_msg = "";
            this.query_msg = "";
            this.data = {
                id: null,
                title: null,
                module: null,
                module_id: null,
                layout: null,
                layout_id: null,
                route: null,
                route_id: null,
                param: null,
                query: null,
                url: null,
                icon: null,
                status: null,
                parent: null,
                parent_id: null,
                desc: null,
                priority: null
            };
        },
        submitCreate() {
            this.name_msg = "";
            this.name_msg_module = "";
            this.layout_msg = "";
            this.route_msg = "";
            this.priority_msg = "";
            this.param_msg = "";
            this.query_msg = "";

            let current_data = _.cloneDeep(this.data);

            if (current_data.status === false) {
                current_data.status = 0;
            } else if (current_data.status === true) {
                current_data.status = 1;
            } else if (current_data.status === null) {
                current_data.status = 0;
            }

            if (current_data.route_id === "--") {
                current_data.route_id = null;
            }

            if (current_data.icon !== null) {
                current_data.icon = current_data.icon[0];
            }

            if (current_data.param === null) {
                current_data.param = "{}";
            }

            if (current_data.query === null) {
                current_data.query = "{}";
            }
            // console.log(current_data);
            // this.hideModal();
            if (current_data.title === "" || current_data.title === null) {
                this.name_msg = "Title/name menu can not be null";
            } else if (current_data.module_id === "" || current_data.module_id === null) {
                this.name_msg_module = "Module can not be null";
            } else if (current_data.layout_id === "" || current_data.layout_id === null) {
                this.layout_msg = "Layout can not be null";
            } else if (isJson(current_data.param) === false) {
                this.param_msg = "Format must be in Json";
            } else if (isJson(current_data.query) === false) {
                this.query_msg = "Format must be in Json";
            } else if (current_data.priority === "" || current_data.priority === null) {
                this.priority_msg = "Priority can not be null";
            } else {
                if (current_data.id !== null) {
                    updateMenu(current_data);
                } else {
                    current_data = _.pickBy(current_data, (val, idx) => {
                        return idx !== "id";
                    });
                    // console.log(this.data);
                    console.log(current_data);
                    addMenu(current_data);
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
            const menu_dataStore = Alpine.store('menu');
            let _data = _.cloneDeep(menu_dataStore.getCurrentDataByIndex(idx));
            // console.table(_data);
            this.title = "Edit Menu - " + _data.id;
            this.data = {
                id: _data.id,
                title: _data.title,
                module: _data.module,
                module_id: _data.module_id,
                layout: _data.layout,
                layout_id: _data.layout_id,
                route: _data.route,
                route_id: _data.route_id,
                param: _data.param,
                query: _data.query,
                url: _data.url,
                icon: _data.icon,
                status: parseInt(_data.status) === 1,
                parent: _data.parent,
                parent_id: _data.parent_id,
                desc: _data.desc,
                priority: _data.priority
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