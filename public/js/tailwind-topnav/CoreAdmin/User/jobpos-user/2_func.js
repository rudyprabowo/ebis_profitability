function reloadData(dbcache=1) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("retrieve data...");
    fetchWithTimeout(
      "/core-admin/mapping-user/jobpos-user/getall?dbcache="+dbcache,
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
          // console.table(json.data);
          const jobposStore = Alpine.store('jobpos');
          jobposStore.setData(json.data);
        }
        mainLoader.hide();
        filterData();
      })
      .catch((error) => {
        console.error(error.message);
        mainLoader.hide();
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Retrieve Data`, `Failed <b>retrieve data</b> from server`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      });
};

function filterData(print = false) {
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

const jobposStore = Alpine.store('jobpos');
jobposStore.filterData({ filter, sorting, limit, current_page }, print);
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

function addJobpos(param) {
const mainLoader = Alpine.store('loader');
mainLoader.show("sending request...");
fetchWithTimeout(
    "/core-admin/mapping-user/jobpos/create",
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
    if (json.ret && json.data.code!==undefined && json.data.code===0) {
        window.dispatchEvent( new CustomEvent('success-create') );
        window.dispatchEvent(new CustomEvent('add-notif', {
        detail: { type:"success", title: "Add Jobpos", msg: `Success add <b>Jobpos ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Add Jobpos`, `Success <b>add jobpos</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
    } else if (json.data.code !== undefined && json.data.code === 1) {
        window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }) );
    } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Add Jobpos`, `Failed <b>add jobpos</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
        });
    }
    })
    .catch((error) => {
    console.error(error.message);
    mainLoader.hide();
    const notifStore = Alpine.store('notif_modal');
    notifStore.show(`Add Jobpos`, `Failed <b>add jobpos</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
    });
    });
};

function changeStatus(param,el,old_val) {
const mainLoader = Alpine.store('loader');
mainLoader.show("sending request...");
fetchWithTimeout(
    "/core-admin/mapping-user/jobpos/updatestatus",
    {
    method: 'POST',
    headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
    },
    body: JSON.stringify({ id: param.id, status: !old_val })
    },
    10000
)
    .then((response) => response.json())
    .then((json) => {
    // console.log(json);
        mainLoader.hide();
    if (json.ret && json.data.code!==undefined && json.data.code===0) {
        window.dispatchEvent(new CustomEvent('success-create'));
        el.checked = !old_val;
        window.dispatchEvent(new CustomEvent('add-notif', {
        detail: { type:"success", title: "Update Jobpos Status", msg: `Success update <b>Jobpos ${param.id} - ${param.name}</b> status`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update Jobpos`, `Success <b>update jobpos</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
    } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update Jobpos Status`, `Failed <b>update jobpos status</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
        });
    }
    })
    .catch((error) => {
    console.error(error.message);
    mainLoader.hide();
    const notifStore = Alpine.store('notif_modal');
    notifStore.show(`Update Jobpos Status`, `Failed <b>update jobpos status</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
    });
    });
};

function removeJobpos(param) {
const mainLoader = Alpine.store('loader');
mainLoader.show("sending request...");
fetchWithTimeout(
    "/core-admin/mapping-user/jobpos/delete",
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
    // console.log(json);
    mainLoader.hide();
    if (json.ret && json.data.code !== undefined && json.data.code === 0) {
        const jobposStore = Alpine.store('jobpos');
        let _data = _.cloneDeep(jobposStore.data);
        _data = _.filter(_data, (v, k) => {
        return v.id!==param.id;
        });
        jobposStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
        detail: { type:"success", title: "Remove Jobpos", msg: `Success remove  <b>Jobpos ${param.id} - ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Jobpos`, `Success <b>remove jobpos</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
    } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Jobpos`, `Failed <b>remove jobpos</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
        });
    }
    })
    .catch((error) => {
    console.error(error.message);
    mainLoader.hide();
    const notifStore = Alpine.store('notif_modal');
    notifStore.show(`Remove Jobpos`, `Failed <b>remove jobpos</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
    });
    });
}

function removeJobpos(param) {
const mainLoader = Alpine.store('loader');
mainLoader.show("sending request...");
fetchWithTimeout(
    "/core-admin/mapping-user/jobpos/delete",
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
    // console.log(json);
    mainLoader.hide();
    if (json.ret && json.data.code !== undefined && json.data.code === 0) {
        const jobposStore = Alpine.store('jobpos');
        let _data = _.cloneDeep(jobposStore.data);
        _data = _.filter(_data, (v, k) => {
        return v.id!==param.id;
        });
        jobposStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
        detail: { type:"success", title: "Remove Jobpos", msg: `Success remove  <b>Jobpos ${param.id} - ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Jobpos`, `Success <b>remove jobpos</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
    } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Jobpos`, `Failed <b>remove jobpos</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
        });
    }
    })
    .catch((error) => {
    console.error(error.message);
    mainLoader.hide();
    const notifStore = Alpine.store('notif_modal');
    notifStore.show(`Remove Jobpos`, `Failed <b>remove jobpos</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
    });
    });
}

function removeMultiJobpos(id) {
// console.log(id);
const mainLoader = Alpine.store('loader');
mainLoader.show("sending request...");
fetchWithTimeout(
    "/core-admin/mapping-user/jobpos/deletemulti",
    {
    method: 'POST',
    headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
    },
    body: JSON.stringify({id:id})
    },
    10000
)
    .then((response) => response.json())
    .then((json) => {
    // console.log(json);
        mainLoader.hide();
    if (json.ret && json.data.code !== undefined && json.data.code === 0) {
        const jobposStore = Alpine.store('jobpos');
        let _data = _.cloneDeep(jobposStore.data);
        _data = _.filter(_data, (v, k) => {
        return !_.includes(id, v.id);
        });
        jobposStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
        detail: { type:"success", title: "Remove Jobpos", msg: `Success <b>remove selected jobpos</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Jobpos`, `Success <b>remove jobpos</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
    } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Jobpos`, `Failed <b>remove jobpos</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
        });
    }
    })
    .catch((error) => {
    console.error(error.message);
    mainLoader.hide();
    const notifStore = Alpine.store('notif_modal');
    notifStore.show(`Remove Jobpos`, `Failed <b>remove jobpos</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
    });
    });
};

const start_dt = document.querySelector('input[name="start_dt"]');
const datepicker = new Datepicker(start_dt, {
    // buttonClass: 'cursor-pointer relative w-full h-5border rounded border-gray-400 bg-white',
  // ...optionsx    
  format : {
    toDisplay(date, format, locale) {
        // let dateString;
        console.log(date);
        // console.log('date');
        // ...your custom format logic
        // return dateString;
    },
  }
}); 