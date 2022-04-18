function reloadData() {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("retrieve data...");
    console.log("TES");
    fetchWithTimeout(
        "/core-admin/manage-script/controller/getall?dbcache=0",
        {
            // mode: "no-cors",
            // redirect: "follow",
            method: 'POST',
            headers: {
                Accept: 'application/json',
                // 'Content-type': 'application/json'
            },
            // body: dummy_data_query
        },
        10000
    )
        .then((response) => response.json())
        .then((json) => {
            if (json.ret) {
                // console.table(json);
                const moduleStore = Alpine.store('controller');
                moduleStore.setData(json.data);
            }
            mainLoader.hide();
            filterData();
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

    const controllerStore = Alpine.store('controller');
    controllerStore.filterData({ filter, sorting, limit, current_page });
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

function addController(param) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-script/controller/create",
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
                window.dispatchEvent(new CustomEvent('success-create'));
                window.dispatchEvent(new CustomEvent('add-notif', {
                    detail: { title: "<span class='text-green-700'>Add Controller</span>", msg: `Success add <b>Controller ${param.name}</b>`, timeout: 5000 }
                }));
                // window.dispatchEvent(new CustomEvent('success-create'));
                // const notifStore = Alpine.store('notif_modal');
                // notifStore.show(`Add Module`, `Success <b>add controller</b>`, "", "success", `CLOSE`, () => {
                //     notifStore.hide();
                // });
            } else if (json.data.code !== undefined && json.data.code === 1) {
                window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }));
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Add Module`, `Failed <b>add controller</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Add Module`, `Failed <b>add controller</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};

function updateController(param) {
    // console.log("update");

    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-script/controller/update",
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
                window.dispatchEvent(new CustomEvent('success-create'));
                window.dispatchEvent(new CustomEvent('add-notif', {
                    detail: { title: "<span class='text-indigo-700'>Update Controller</span>", msg: `Success update <b>Controller ${param.id} - ${param.name}</b>`, timeout: 5000 }
                }));
                // const notifStore = Alpine.store('notif_modal');
                // notifStore.show(`Update Module`, `Success <b>update module</b>`, "", "success", `CLOSE`, () => {
                //   notifStore.hide();
                // });
            } else if (json.data.code !== undefined && json.data.code === 1) {
                window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }));
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Update Module`, `Failed <b>update controller</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Update Module`, `Failed <b>update controller</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};

function removeController(param) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-script/controller/delete",
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
                const controllerStore = Alpine.store('controller');
                let _data = _.cloneDeep(controllerStore.data);
                _data = _.filter(_data, (v, k) => {
                    return v.id !== param.id;
                });
                controllerStore.setData(_data);
                filterData();
                window.dispatchEvent(new CustomEvent('add-notif', {
                    detail: { title: "<span class='text-red-700'>Remove Controller</span>", msg: `Success remove  <b>Controller ${param.id} - ${param.name}</b>`, timeout: 5000 }
                }));
                // const notifStore = Alpine.store('notif_modal');
                // notifStore.show(`Remove Module`, `Success <b>remove module</b>`, "", "success", `CLOSE`, () => {
                //   notifStore.hide();
                // });
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Remove Controller`, `Failed <b>remove Controller</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Remove Module`, `Failed <b>remove module</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
}

function removeMultiModule(id) {
    // console.log(id);
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-script/controller/deletemulti",
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
                const controllerStore = Alpine.store('controller');
                let _data = _.cloneDeep(controllerStore.data);
                _data = _.filter(_data, (v, k) => {
                    return !_.includes(id, v.id);
                });
                controllerStore.setData(_data);
                filterData();
                window.dispatchEvent(new CustomEvent('add-notif', {
                    detail: { title: "<span class='text-red-700'>Remove Controller</span>", msg: `Success <b>remove selected controller</b>`, timeout: 5000 }
                }));
                // const notifStore = Alpine.store('notif_modal');
                // notifStore.show(`Remove Module`, `Success <b>remove module</b>`, "", "success", `CLOSE`, () => {
                //   notifStore.hide();
                // });
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Remove Controller`, `Failed <b>remove controller</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Remove Module`, `Failed <b>remove module</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};