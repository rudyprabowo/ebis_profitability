function reloadData(param) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("retrieve data...");
    console.log("TES");
    fetchWithTimeout(
        "/core-admin/manage-role/user-role/getall?dbcache=0",
        {
            // mode: "no-cors",
            // redirect: "follow",
            method: 'POST',
            headers: {
                Accept: 'application/json',
                // 'Content-type': 'application/json'
            },
            body: JSON.stringify({ id: param })
            // body: dummy_data_query
        },
        10000
    )
        .then((response) => response.json())
        .then((json) => {
            if (json.ret) {
                // console.log(json.data.length);
                const roleStore = Alpine.store('user_role');
                roleStore.setData(json.data);
            }
            mainLoader.hide();
            filterData();
            // if (json.data.length !== 0) {
            //     filterData();
            // }
        })
        .catch((error) => {
            console.log(error);
            mainLoader.main = false;
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Retrieve Data`, `Failed <b>retrieve data</b> from server`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};

function filterData() {
    const filterStore = Alpine.store('filter');
    let filter = _.cloneDeep(filterStore.data);
    filter = _.pickBy(filter, (val, idx) => {
        return val.cond !== "none" && val.val !== null;
    });
    const sortingStore = Alpine.store('sorting');
    let sorting = _.cloneDeep(sortingStore.data);
    const pagingStore = Alpine.store('paging');
    let { limit, current_page } = _.cloneDeep(pagingStore.data);
    // console.log({ filter, sorting, limit, current_page });
    if (current_page === 0) {
        current_page = 1;
    }
    const roleStore = Alpine.store('user_role');
    roleStore.filterData({ filter, sorting, limit, current_page });
}

function filteringData(data, field, cond, val) {
    let ret = false;
    // console.log({ data, field });
    if (data[field] !== undefined) {
        let _data = _.toString(data[field]);
        // console.log({ data, field, _data, cond, val });
        let _val = val.split(",");
        _val = _val.map(v => {
            return v.trim();
        });
        switch (cond) {
            case 'prefix':
                for (let check of _val) {
                    if (_.startsWith(_data, check)) {
                        ret = true;
                        break;
                    }
                }
                break;
            case 'suffix':
                for (let check of _val) {
                    if (_.endsWith(_data, check)) {
                        ret = true;
                        break;
                    }
                }
                break;
            case 'equal':
                for (let check of _val) {
                    if (_.eq(_data, check)) {
                        ret = true;
                        break;
                    }
                }
                break;
            case 'like':
                for (let check of _val) {
                    if (_.includes(_data, check)) {
                        ret = true;
                        break;
                    }
                }
                break;
            case 'notprefix':
                ret = true;
                for (let check of _val) {
                    if (_.startsWith(_data, check)) {
                        ret = false;
                        break;
                    }
                }
                break;
            case 'notsuffix':
                ret = true;
                for (let check of _val) {
                    if (_.endsWith(_data, check)) {
                        ret = false;
                        break;
                    }
                }
                break;
            case 'notequal':
                ret = true;
                for (let check of _val) {
                    if (_.eq(_data, check)) {
                        ret = false;
                        break;
                    }
                }
                break;
            case 'like':
                ret = true;
                for (let check of _val) {
                    if (_.includes(_data, check)) {
                        ret = false;
                        break;
                    }
                }
                break;
            default:
                ret = false;
                break;
        }
    }

    return ret;
}

function setNewrole(param) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-role/user-role/create",
        {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-type': 'application/json'
            },
            body: JSON.stringify(param)
        },
        10000
    )
        .then((response) => response.json())
        .then((json) => {
            // console.log(json);
            mainLoader.hide();
            if (json.ret && json.data.code !== undefined && json.data.code === 0) {
                // window.dispatchEvent(new CustomEvent('success-create'));
                // window.dispatchEvent(new CustomEvent('add-notif', {
                //     detail: { title: "<span class='text-green-700'>Set Role</span>", msg: `Success set <b>Role ${param.user}</b>`, timeout: 5000 }
                // }));

                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Set Role`, `Success <b>Set Role</b>`, "", "success", `CLOSE`, () => {
                    notifStore.hide();
                    reloadData(param.user);
                });

            } else if (json.data.code !== undefined && json.data.code === 1) {
                // window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }));
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Set Role`, `Failed <b>Set Role</b>`, json.data.msg, "failed", `CLOSE`, () => {
                    notifStore.hide();
                    reloadData(param.user);
                });
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Set Role`, `Failed <b>set role</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Set Role`, `Failed <b>set role</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};

function updateRole(param) {
    // console.log("update");

    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-role/user-role/update",
        {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-type': 'application/json'
            },
            body: JSON.stringify(param)
        },
        10000
    )
        .then((response) => response.json())
        .then((json) => {
            // console.log(json);
            mainLoader.hide();
            if (json.ret && json.data.code !== undefined && json.data.code === 0) {
                // window.dispatchEvent(new CustomEvent('success-create'));
                // window.dispatchEvent(new CustomEvent('add-notif', {
                //     detail: { title: "<span class='text-indigo-700'>Update Role</span>", msg: `Success update <b>Role ${param.id} - ${param.name}</b>`, timeout: 5000 }
                // }));
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Update Role`, `Success <b>Update Role</b>`, "", "success", `CLOSE`, () => {
                    notifStore.hide();
                    reloadData(param.user);
                });
            } else if (json.data.code !== undefined && json.data.code === 1) {
                window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }));
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Update Role`, `Failed <b>Update Role</b>`, json.data.msg, "failed", `CLOSE`, () => {
                    notifStore.hide();
                    reloadData(param.user);
                });
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Update Role`, `Failed <b>update role</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Update Role`, `Failed <b>update role</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};

function removeRole(param) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-role/user-role/delete",
        {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-type': 'application/json'
            },
            body: JSON.stringify({ id: param.id })
        },
        10000
    )
        .then((response) => response.json())
        .then((json) => {
            console.log(json);
            mainLoader.hide();
            if (json.ret && json.data.code !== undefined && json.data.code === 0) {
                const roleStore = Alpine.store('user_role');
                let _data = _.cloneDeep(roleStore.data);
                _data = _.filter(_data, (v, k) => {
                    return v.id !== param.id;
                });
                roleStore.setData(_data);
                filterData();
                // window.dispatchEvent(new CustomEvent('add-notif', {
                //     detail: { title: "<span class='text-red-700'>Remove Role</span>", msg: `Success remove  <b>Role ${param.id} - ${param.name}</b>`, timeout: 5000 }
                // }));
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Delete Role`, `Success <b>Delete Role</b>`, "", "success", `CLOSE`, () => {
                    notifStore.hide();
                    // reloadData(param.user);
                });
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Remove Role`, `Failed <b>remove Role</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Remove Layout`, `Failed <b>remove layout</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
}

function removeMultiRole(id) {
    // console.log(id);
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-role/user-role/deletemulti",
        {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        },
        10000
    )
        .then((response) => response.json())
        .then((json) => {
            // console.log(json);
            mainLoader.hide();
            if (json.ret && json.data.code !== undefined && json.data.code === 0) {
                const roleStore = Alpine.store('user_role');
                let _data = _.cloneDeep(roleStore.data);
                _data = _.filter(_data, (v, k) => {
                    return !_.includes(id, v.id);
                });
                roleStore.setData(_data);
                filterData();
                // window.dispatchEvent(new CustomEvent('add-notif', {
                //     detail: { title: "<span class='text-red-700'>Remove Role</span>", msg: `Success <b>remove selected role</b>`, timeout: 5000 }
                // }));
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Delete Role`, `Success <b>Delete Role</b>`, "", "success", `CLOSE`, () => {
                    notifStore.hide();
                    // reloadData(param.user);
                });
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Remove Role`, `Failed <b>remove role</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Remove Layout`, `Failed <b>remove layout</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};

function updatestatusRole(param) {
    // console.log("update");

    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-role/user-role/updatestatus",
        {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-type': 'application/json'
            },
            body: JSON.stringify(param)
        },
        10000
    )
        .then((response) => response.json())
        .then((json) => {
            // console.log(json);
            mainLoader.hide();
            if (json.ret && json.data.code !== undefined && json.data.code === 0) {
                // window.dispatchEvent(new CustomEvent('success-create'));
                // window.dispatchEvent(new CustomEvent('add-notif', {
                //     detail: { title: "<span class='text-indigo-700'>Update status Role</span>", msg: `Success update <b>status Role ${param.id} - ${param.name}</b>`, timeout: 5000 }
                // }));
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Update status Role`, `Success <b>update status role</b>`, "", "success", `CLOSE`, () => {
                    notifStore.hide();
                });
            } else if (json.data.code !== undefined && json.data.code === 1) {
                window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }));
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Update status Role`, `Failed <b>update status role</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Update status Role`, `Failed <b>update status role</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};