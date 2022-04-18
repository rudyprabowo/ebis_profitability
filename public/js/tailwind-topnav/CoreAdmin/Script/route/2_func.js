function reloadData(dbcache=1) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("retrieve data...");
    fetchWithTimeout(
      "/core-admin/manage-script/route/getall?dbcache="+dbcache,
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
          const routeStore = Alpine.store('route');
          routeStore.setData(json.data);
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

const routeStore = Alpine.store('route');
routeStore.filterData({ filter, sorting, limit, current_page });
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

function addRoute(param) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
      "/core-admin/manage-script/route/create",
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
            detail: { title: "<span class='text-green-700'>Add Route</span>", msg: `Success add <b>Route ${param.name}</b>`, timeout:5000 }
          }));
          // const notifStore = Alpine.store('notif_modal');
          // notifStore.show(`Add Route`, `Success <b>add route</b>`, "", "success", `CLOSE`, () => {
          //   notifStore.hide();
          // });
        } else if (json.data.code !== undefined && json.data.code === 1) {
          window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }) );
        } else {
          const notifStore = Alpine.store('notif_modal');
          notifStore.show(`Add Route`, `Failed <b>add route</b>, please try again`, "", "failed", `CLOSE`, () => {
            notifStore.hide();
          });
        }
      })
      .catch((error) => {
        console.error(error.message);
        mainLoader.hide();
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Add Route`, `Failed <b>add route</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      });
};

function updateRoute(param) {
  console.log(param);
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/update",
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
          const routeStore = Alpine.store('route');
          let _data = _.cloneDeep(routeStore.data);
          _data = _.filter(_data, (v, k) => {
            return v.id!==param.id;
          });
          routeStore.setData(_data);
          filterData();
          window.dispatchEvent(new CustomEvent('add-notif', {
            detail: { title: "<span class='text-red-700'>Update Route</span>", msg: `Success update  <b>Route ${param.id} - ${param.name}</b>`, timeout:5000 }
          }));
          // const notifStore = Alpine.store('notif_modal');
          // notifStore.show(`Remove Route`, `Success <b>update route</b>`, "", "success", `CLOSE`, () => {
          //   notifStore.hide();
          // });
        } else {
          const notifStore = Alpine.store('notif_modal');
          notifStore.show(`Update Route`, `Failed <b>update route</b>, please try again`, "", "failed", `CLOSE`, () => {
            notifStore.hide();
          });
        }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Route`, `Failed <b>update route</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeStatus(param,el,old_val) {
    const mainLoader = Alpine.store('loader');
    mainLoader.show("sending request...");
    fetchWithTimeout(
      "/core-admin/manage-script/route/updatestatus",
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
            detail: { title: "<span class='text-indigo-700'>Update Route Status</span>", msg: `Success update <b>Route ${param.id} - ${param.name}</b> status`, timeout:5000 }
          }));
          // const notifStore = Alpine.store('notif_modal');
          // notifStore.show(`Update Route`, `Success <b>update route</b>`, "", "success", `CLOSE`, () => {
          //   notifStore.hide();
          // });
        } else {
          const notifStore = Alpine.store('notif_modal');
          notifStore.show(`Update Route Status`, `Failed <b>update route status</b>, please try again`, "", "failed", `CLOSE`, () => {
            notifStore.hide();
          });
        }
      })
      .catch((error) => {
        console.error(error.message);
        mainLoader.hide();
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update Route Status`, `Failed <b>update route status</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      });
};

function changeShow_title(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/updateshow_title",
    {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
      },
      body: JSON.stringify({ id: param.id, show_title: !old_val })
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
          detail: { title: "<span class='text-indigo-700'>Update Route Show Title</span>", msg: `Success update <b>Route ${param.id} - ${param.name}</b> show title`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update Route`, `Success <b>update route</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update Route show title`, `Failed <b>update route show title</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Route show title`, `Failed <b>update route show title</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeMay_terminate(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/updatemay_terminate",
    {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
      },
      body: JSON.stringify({ id: param.id, may_terminate: !old_val })
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
          detail: { title: "<span class='text-indigo-700'>Update Route May Terminate</span>", msg: `Success update <b>Route ${param.id} - ${param.name}</b> may terminate`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update Route`, `Success <b>update route</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update Route May Terminate`, `Failed <b>update route may terminate</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Route May Terminate`, `Failed <b>update route may terminate</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeIs_caching(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/updateis_caching",
    {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
      },
      body: JSON.stringify({ id: param.id, is_caching: !old_val })
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
          detail: { title: "<span class='text-indigo-700'>Update Route Is Caching</span>", msg: `Success update <b>Route ${param.id} - ${param.name}</b> is caching`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update Route`, `Success <b>update route</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update Route Is Caching`, `Failed <b>update route is caching</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Route Is Caching`, `Failed <b>update route is caching</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeIs_logging(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/updateis_logging",
    {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
      },
      body: JSON.stringify({ id: param.id, is_logging: !old_val })
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
          detail: { title: "<span class='text-indigo-700'>Update Route Is Logging</span>", msg: `Success update <b>Route ${param.id} - ${param.name}</b> is logging`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update Route`, `Success <b>update route</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update Route Is Logging`, `Failed <b>update route is logging</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Route Is Logging`, `Failed <b>update route is logging</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeIs_public(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/updateis_public",
    {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
      },
      body: JSON.stringify({ id: param.id, is_public: !old_val })
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
          detail: { title: "<span class='text-indigo-700'>Update Route Is Public</span>", msg: `Success update <b>Route ${param.id} - ${param.name}</b> is public`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update Route`, `Success <b>update route</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update Route Is Public`, `Failed <b>update route is public</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Route Is Public`, `Failed <b>update route is public</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeIs_api(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/updateis_api",
    {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
      },
      body: JSON.stringify({ id: param.id, is_api: !old_val })
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
          detail: { title: "<span class='text-indigo-700'>Update Route Is Api</span>", msg: `Success update <b>Route ${param.id} - ${param.name}</b> is api`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update Route`, `Success <b>update route</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update Route Is Api`, `Failed <b>update route is api</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Route Is Api`, `Failed <b>update route is api</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function removeRoute(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/delete",
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
        const routeStore = Alpine.store('route');
        let _data = _.cloneDeep(routeStore.data);
        _data = _.filter(_data, (v, k) => {
          return v.id!==param.id;
        });
        routeStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { title: "<span class='text-red-700'>Remove Route</span>", msg: `Success remove  <b>Route ${param.id} - ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Route`, `Success <b>remove route</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Route`, `Failed <b>remove route</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove Route`, `Failed <b>remove route</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
}

function removeRoute(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/delete",
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
        const routeStore = Alpine.store('route');
        let _data = _.cloneDeep(routeStore.data);
        _data = _.filter(_data, (v, k) => {
          return v.id!==param.id;
        });
        routeStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { title: "<span class='text-red-700'>Remove Route</span>", msg: `Success remove  <b>Route ${param.id} - ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Route`, `Success <b>remove route</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Route`, `Failed <b>remove route</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove Route`, `Failed <b>remove route</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
}

function removeMultiRoute(id) {
  // console.log(id);
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-script/route/deletemulti",
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
        const routeStore = Alpine.store('route');
        let _data = _.cloneDeep(routeStore.data);
        _data = _.filter(_data, (v, k) => {
          return !_.includes(id, v.id);
        });
        routeStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { title: "<span class='text-red-700'>Remove Route</span>", msg: `Success <b>remove selected route</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Route`, `Success <b>remove route</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Route`, `Failed <b>remove route</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove Route`, `Failed <b>remove route</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

// new TomSelect("#select-tags",{
//     plugins: ['remove_button'],
// });
    
new TomSelect("#select-method",{
	// maxItems: 2,
  plugins: ['remove_button'],

});