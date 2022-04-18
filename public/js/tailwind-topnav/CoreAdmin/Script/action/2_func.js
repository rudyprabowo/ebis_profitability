function reloadData() {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("retrieve data...");
  fetchWithTimeout(
      "/core-admin/manage-script/action/getall?dbcache=0", {
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
        // console.log("test");
        // console.table(json.data);
        const actionStore = Alpine.store('action');
        actionStore.setData(json.data);
      }
      mainLoader.hide();
      filterData();
    })
    .catch((error) => {
      console.error(error.message);
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
  let {
    limit,
    current_page
  } = _.cloneDeep(pagingStore.data);
  // console.log({ filter, sorting, limit, current_page });

  const actionStore = Alpine.store('action');
  actionStore.filterData({
    filter,
    sorting,
    limit,
    current_page
  });
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

function addAction(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/action/create",
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
            detail: { title: "<span class='text-green-700'>Add Action</span>", msg: `Success add <b>Action ${param.name}</b>`, timeout:5000 }
          }));
          // const notifStore = Alpine.store('notif_modal');
          // notifStore.show(`Add Action`, `Success <b>add action</b>`, "", "success", `CLOSE`, () => {
          //   notifStore.hide();
          // });
        } else if (json.data.code !== undefined && json.data.code === 1) {
          window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }) );
        } else {
          const notifStore = Alpine.store('notif_modal');
          notifStore.show(`Add Action`, `Failed <b>add action</b>, please try again`, "", "failed", `CLOSE`, () => {
            notifStore.hide();
          });
        }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Add Action`, `Failed <b>add action</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function updateAction(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/action/update",
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
          const actionStore = Alpine.store('action');
          let _data = _.cloneDeep(actionStore.data);
          _data = _.filter(_data, (v, k) => {
            return v.id!==param.id;
          });
          actionStore.setData(_data);
          filterData();
          window.dispatchEvent(new CustomEvent('add-notif', {
            detail: { title: "<span class='text-red-700'>Update Action</span>", msg: `Success update  <b>Action ${param.id} - ${param.name}</b>`, timeout:5000 }
          }));
          // const notifStore = Alpine.store('notif_modal');
          // notifStore.show(`Remove Action`, `Success <b>update action</b>`, "", "success", `CLOSE`, () => {
          //   notifStore.hide();
          // });
        } else {
          const notifStore = Alpine.store('notif_modal');
          notifStore.show(`Update Action`, `Failed <b>update action</b>, please try again`, "", "failed", `CLOSE`, () => {
            notifStore.hide();
          });
        }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Action`, `Failed <b>update action</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeStatus(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/action/updatestatus",
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
            detail: { title: "<span class='text-indigo-700'>Update Action Status</span>", msg: `Success update <b>Action ${param.id} - ${param.name}</b> status`, timeout:5000 }
          }));
          // const notifStore = Alpine.store('notif_modal');
          // notifStore.show(`Update Action`, `Success <b>update action</b>`, "", "success", `CLOSE`, () => {
          //   notifStore.hide();
          // });
        } else {
          const notifStore = Alpine.store('notif_modal');
          notifStore.show(`Update Action Status`, `Failed <b>update action status</b>, please try again`, "", "failed", `CLOSE`, () => {
            notifStore.hide();
          });
        }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Action Status`, `Failed <b>update action status</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function removeAction(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/action/delete",
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
        const actionStore = Alpine.store('action');
        let _data = _.cloneDeep(actionStore.data);
        _data = _.filter(_data, (v, k) => {
          return v.id!==param.id;
        });
        actionStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { title: "<span class='text-red-700'>Remove Action</span>", msg: `Success remove  <b>Action ${param.id} - ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Action`, `Success <b>remove action</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Action`, `Failed <b>remove action</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove Action`, `Failed <b>remove action</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
}

function removeAction(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/action/delete",
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
        const actionStore = Alpine.store('action');
        let _data = _.cloneDeep(actionStore.data);
        _data = _.filter(_data, (v, k) => {
          return v.id!==param.id;
        });
        actionStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { title: "<span class='text-red-700'>Remove Action</span>", msg: `Success remove  <b>Action ${param.id} - ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Action`, `Success <b>remove action</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Action`, `Failed <b>remove action</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove Action`, `Failed <b>remove action</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
}

function removeMultiAction(id) {
  // console.log(id);
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/action/deletemulti",
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
        const actionStore = Alpine.store('action');
        let _data = _.cloneDeep(actionStore.data);
        _data = _.filter(_data, (v, k) => {
          return !_.includes(id, v.id);
        });
        actionStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { title: "<span class='text-red-700'>Remove Action</span>", msg: `Success <b>remove selected action</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Action`, `Success <b>remove action</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Action`, `Failed <b>remove action</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove Action`, `Failed <b>remove action</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};