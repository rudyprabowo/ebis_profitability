document.addEventListener('alpine:init', () => {
    Alpine.data('list_user', () => ({
        data: [],
        open: true,
        show: false,
        showModalDelete(idx) {
            this.show = true;
            console.log("showModalDelete");
            console.log(this.show);
            console.log(idx);
            // this.removeUser(idx);
        },
        init() {
            const mainLoader = Alpine.store('loader');
            mainLoader.main = true;

            let _data = _.cloneDeep(this.data);
            // console.log(_data);

            // let formData = new FormData();
            // formData = objectToFormData(_data, formData);
            let csrf = getCSRFToken();
            fetchWithTimeout(
                    `/core-admin/manage-user/process/_/filter`, {
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
                        var retAjax = json.data.row;
                        console.log(retAjax);
                        this.setData(retAjax);
                    } else {
                        mainLoader.main = false;
                    }
                })
                .catch((error) => {
                    console.error(error.message);
                    mainLoader.main = false;
                    let notifStore = Alpine.store('notif_modal');
                    notifStore.show(`Data User`, `Failed <b>Select Data</b>`, "", "failed", `OK`, () => {
                        notifStore.hide();
                    });
                });

            // var retAjax = [{
            //     id: 1,
            //     username: "A",
            //     email: "a@mail.com",
            //     full_name: "A Name",
            //     status: 1
            //   },
            //   {
            //     id: 2,
            //     username: "B",
            //     email: "b@mail.com",
            //     full_name: "B Name",
            //     status: 0
            //   },
            //   {
            //     id: 3,
            //     username: "C",
            //     email: "c@mail.com",
            //     full_name: "C Name",
            //     status: 0
            //   },
            //   {
            //     id: 4,
            //     username: "D",
            //     email: "d@mail.com",
            //     full_name: "D Name",
            //     status: 1
            //   }
            // ];
            // this.setData(retAjax);

        },
        event: {
            ['@get-userbyidx.window']() {
                // console.log(this.$event.detail);
                if (this.data[this.$event.detail] !== undefined) {
                    active_user_edit = _.cloneDeep(this.data[this.$event.detail]);
                } else {
                    active_user_edit = null;
                }

            }
        },
        setData(data) {
            this.data = data;
        },
        changeUserStatus(idx) {
            // console.log(idx);
            let newstatus = 0;
            if (parseInt(this.data[idx].status) !== 1) {
                newstatus = 1;
            }
            let uid = this.data[idx].id;

            //-----------------------------call ajax
            const mainLoader = Alpine.store('loader');
            mainLoader.main = true;

            // let _data = _.cloneDeep(this.data);

            // let formData = new FormData();
            // formData = objectToFormData(_data, formData);
            let csrf = getCSRFToken();
            fetchWithTimeout(
                    `/core-admin/manage-user/process/${uid}/edit_status`, {
                        method: 'POST',
                        redirect: 'error',
                        headers: {
                            Accept: 'application/json',
                            'Content-type': 'application/json',
                            [csrf.name]: csrf.val
                        },
                        body: JSON.stringify(newstatus)
                    },
                    10000
                )
                .then((response) => response.json())
                .then((json) => {
                    // console.log(json);
                    if (json.ret) {
                        mainLoader.main = false;
                        if (json.process) {
                            this.data[idx].status = newstatus;
                        } else {
                            let notifStore = Alpine.store('notif_modal');
                            notifStore.show(`Edit Status`, `Failed <b>Edit Status</b>`, json.msg, "failed", `OK`, () => {
                                notifStore.hide();
                            });
                        }
                    } else {
                        mainLoader.main = false;
                    }
                })
                .catch((error) => {
                    console.log(error);
                    mainLoader.main = false;
                    let notifStore = Alpine.store('notif_modal');
                    notifStore.show(`Edit Status`, `Failed <b>Edit Status</b>`, "", "failed", `OK`, () => {
                        notifStore.hide();
                    });
                });

            // let retAjax = true;
            // if (retAjax) {
            //   this.data[idx].status = newstatus;
            // }
        },
        removeUser(idx) {
            let uid = this.data[idx].id;
            //call ajax

            // let retAjax = true;
            // if (retAjax) {
            //     _.pullAt(this.data, [idx]);
            // }

        }
        // modalAddUser() {
        //   this.open = ! this.open;
        // },
        // addUser() {
        //   let newUser = {
        //     id: null,
        //     username: "X",
        //     email: "x@mail.com",
        //     full_name: "X Name",
        //     status: 1
        //   };

        //   //call ajax
        //   let retAjax = true;
        //   let retAjaxNewId = 10;
        //   if (retAjax) {
        //     newUser.id = retAjaxNewId;
        //     this.data.push(newUser);
        //   }
        // }

    }));

    Alpine.data('add_user', () => ({
        show: false,
        notif: false,
        name_msg: "",
        data: {
            username: null,
            full_name: null,
            email: null,
            mobile_no: null,
            employ_nik: null,
            spv_nik: null,
            redirect_route: null,
            redirect_param: null,
            redirect_url: null,
            redirect_query: null,
            telegram_id: null,
            status: 0,
            is_ldap: 0
        },
        event: {
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
        changeLdap() {
            if (this.data.is_ldap === 1) {
                this.data.is_ldap = 0;
            } else {
                this.data.is_ldap = 1;
            }
        },
        submit() {
            let _data = _.cloneDeep(this.data);
            // console.log(_data);

            let csrf = getCSRFToken();
            this.name_msg = "";


            if (this.data.username !== null && this.data.username !== undefined && this.data.username !== "") {
                const mainLoader = Alpine.store('loader');
                mainLoader.main = true;
                fetchWithTimeout(
                        `/core-admin/manage-user/process/_/add`, {
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
                            let notifStore = Alpine.store('notif_modal');
                            notifStore.show(`Create User`, `Failed <b>Create User</b>`, "", "success", `OK`, () => {
                                notifStore.hide();
                            });
                            mainLoader.main = false;
                        } else {
                            if (json.ret == false && json.msg != "FAILED") {
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
                this.name_msg = "Username or email cannot be null";
                this.notif = true;
            }

        },

    }))

    Alpine.data('edit_user', () => ({
        show: false,
        data: {
            username: null,
            full_name: null,
            email: null,
            mobile_no: null,
            employ_nik: null,
            spv_nik: null,
            redirect_route: null,
            redirect_param: null,
            redirect_url: null,
            redirect_query: null,
            telegram_id: null,
            status: 0,
            is_ldap: 0
        },
        route_opt: [],
        init() {
            this.route_opt = list_route;
        },
        event: {
            ['@show-editmodal.window']() {
                this.$dispatch('get-userbyidx', this.$event.detail);
                console.log(active_user_edit);
                this.showModal();
            }
        },
        initForm() {
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
            this.data = active_user_edit;

        },
        clearForm() {
            this.data.username = null;
            this.data.full_name = null;
            active_user_edit = null;
        },
        showModal() {
            this.initForm();
            this.show = true;
        },
        hideModal() {
            this.show = false;
            this.clearForm();
        },
        changeStatus() {
            if (this.data.status === 1) {
                this.data.status = 0;
            } else {
                this.data.status = 1;
            }
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
        },
        changeStatusLdap() {
            let newstatus = 0;
            if (parseInt(this.data.is_ldap) !== 1) {
                newstatus = 1;
            }
            this.data.is_ldap = newstatus;
            // let uid = this.data[idx].id;
        }
    }))

    // Alpine.data('delete_user', () => ({
    //     deleteAction(idx) {
    //         let notifStore = Alpine.store('notif_modal');
    //         notifStore.show(`Edit User`, `Failed <b>Edit Data</b>`, "", "failed", `OK`, () => {
    //             console.log(idx);
    //         });
    //     }
    // }))

    Alpine.data('filter_user', () => ({
        show: false,
        data: {
            condition_uid: null,
            val_uid: null,
            condition_uname: null,
            val_uname: null,
            condition_full_name: null,
            val_full_name: null,
            condition_email: null,
            val_email: null,
            val_status: null
        },
        event: {
            ['@show-filtermodal.window']() {
                this.showModal();
            }
        },
        showModal() {
            this.show = true;
        },
        hideModal() {
            this.show = false;
            this.clearForm();
        },
        submit() {
            console.log(this.data);
        },
        clearForm() {
            this.data.condition_uid = null;
            this.data.val_uid = null;
            this.data.condition_uname = null;
            this.data.val_uname = null;
            this.data.condition_full_name = null;
            this.data.val_full_name = null;
            this.data.condition_email = null;
            this.data.val_email = null;
            this.data.val_status = null;
            console.log(this.data);
        }
    }))

    Alpine.data('delete_user', () => ({
        show: false,
        data: {
            condition_uid: null,
            val_uid: null,
            condition_uname: null,
            val_uname: null,
            condition_full_name: null,
            val_full_name: null,
            condition_email: null,
            val_email: null,
            val_status: null
        },
        event: {
            ['@show-deletemodal.window']() {
                console.log(this.$event.detail);
                this.showModal();
            }
        },
        showModal() {
            this.show = true;
        },
        hideModal() {
            this.show = false;
        },
        submit() {
            console.log(this.data);
        },
    }))

});

document.addEventListener('alpine:initialized', () => {

});