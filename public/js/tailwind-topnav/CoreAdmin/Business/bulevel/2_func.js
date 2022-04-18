function reloadData() {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("retrieve data...");
    console.log("TES");
    fetchWithTimeout(
        "/core-admin/manage-business/bulevel/getall?dbcache=0",
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
                console.table(json.data);
                const businessStore = Alpine.store('business');
                businessStore.setData(json.data);
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

    const businessStore = Alpine.store('business');
    businessStore.filterData({ filter, sorting, limit, current_page });
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

function addBusinesslevel(param) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-business/bulevel/create",
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
                param.id = json.data.generated_value;
                param.parent = param.parent_id;
                delete param.parent_id;
                window.dispatchEvent(new CustomEvent('success-create',{detail:param}));
                window.dispatchEvent(new CustomEvent('add-notif', {
                    detail: { title: "<span class='text-green-700'>Add Business Unit Level</span>", msg: `Success add <b>Business Unit Level ${param.name}</b>`, timeout: 5000 }
                }));
                // location.reload();
                // window.dispatchEvent(new CustomEvent('success-create'));
                // const notifStore = Alpine.store('notif_modal');
                // notifStore.show(`Add Module`, `Success <b>add controller</b>`, "", "success", `CLOSE`, () => {
                //     notifStore.hide();
                // });
            } else if (json.data.code !== undefined && json.data.code === 1) {
                // window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }));
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Add Business Unit Level`, `Failed <b>add Business Unit Level</b>, please try again`, json.data.msg, "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Add Business Unit Level`, `Failed <b>add Business Unit Level</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Add Business Unit Level`, `Failed <b>add Business Unit Level</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};

function updatebusinessLevel(param) {
    // console.log("update");

    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-business/bulevel/update",
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
                    detail: { title: "<span class='text-indigo-700'>Update business level</span>", msg: `Success update <b>business level ${param.id} - ${param.name}</b>`, timeout: 5000 }
                }));
                // const notifStore = Alpine.store('notif_modal');
                // notifStore.show(`Update Module`, `Success <b>update module</b>`, "", "success", `CLOSE`, () => {
                //   notifStore.hide();
                // });
            } else if (json.data.code !== undefined && json.data.code === 1) {
                window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }));
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Update business level`, `Failed <b>update business level</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Update business level`, `Failed <b>update business level</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};

function removebusinesslevel(param) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-business/bulevel/delete",
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
                const businessStore = Alpine.store('business');
                let _data = _.cloneDeep(businessStore.data);
                _data = _.filter(_data, (v, k) => {
                    return v.id !== param.id;
                });
                businessStore.setData(_data);
                filterData();
                window.dispatchEvent(new CustomEvent('add-notif', {
                    detail: { title: "<span class='text-red-700'>Remove business level</span>", msg: `Success remove  <b>business level ${param.id} - ${param.name}</b>`, timeout: 5000 }
                }));
                // const notifStore = Alpine.store('notif_modal');
                // notifStore.show(`Remove Module`, `Success <b>remove module</b>`, "", "success", `CLOSE`, () => {
                //   notifStore.hide();
                // });
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Remove business level`, `Failed <b>remove business level</b>, please try again`, "", "failed", `CLOSE`, () => {
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

function removeMultibusinesslevel(id) {
    // console.log(id);
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-business/bulevel/deletemulti",
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
                const businessStore = Alpine.store('business');
                let _data = _.cloneDeep(businessStore.data);
                _data = _.filter(_data, (v, k) => {
                    return !_.includes(id, v.id);
                });
                businessStore.setData(_data);
                filterData();
                window.dispatchEvent(new CustomEvent('add-notif', {
                    detail: { title: "<span class='text-red-700'>Remove business level</span>", msg: `Success <b>remove selected business level</b>`, timeout: 5000 }
                }));
                // const notifStore = Alpine.store('notif_modal');
                // notifStore.show(`Remove Module`, `Success <b>remove module</b>`, "", "success", `CLOSE`, () => {
                //   notifStore.hide();
                // });
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Remove business level`, `Failed <b>remove business level</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Remove business level`, `Failed <b>remove business level</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};

function updatestatusbusinesslevel(param) {
    // console.log("update");

    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
        "/core-admin/manage-business/bulevel/updatestatus",
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
                    detail: { title: "<span class='text-indigo-700'>Update status business level</span>", msg: `Success update <b>status business level ${param.id} - ${param.name}</b>`, timeout: 5000 }
                }));
                // const notifStore = Alpine.store('notif_modal');
                // notifStore.show(`Update Module`, `Success <b>update module</b>`, "", "success", `CLOSE`, () => {
                //   notifStore.hide();
                // });
            } else if (json.data.code !== undefined && json.data.code === 1) {
                window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }));
            } else {
                const notifStore = Alpine.store('notif_modal');
                notifStore.show(`Update status business level`, `Failed <b>update status business level</b>, please try again`, "", "failed", `CLOSE`, () => {
                    notifStore.hide();
                });
            }
        })
        .catch((error) => {
            console.error(error.message);
            mainLoader.hide();
            const notifStore = Alpine.store('notif_modal');
            notifStore.show(`Update status business level`, `Failed <b>update status business level</b>, please try again`, "", "failed", `CLOSE`, () => {
                notifStore.hide();
            });
        });
};