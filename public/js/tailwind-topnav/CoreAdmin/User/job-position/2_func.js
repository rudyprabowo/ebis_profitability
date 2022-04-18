function reloadData(dbcache=1) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("retrieve data...");
  fetchWithTimeout(
    "/core-admin/mapping-user/job-position/getall?dbcache="+dbcache,
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
        const jobStore = Alpine.store('job');
        jobStore.setData(json.data);
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
    let {
        limit,
        current_page
    } = _.cloneDeep(pagingStore.data);
    // console.log({ filter, sorting, limit, current_page });

    const jobStore = Alpine.store('job');
    jobStore.filterData({
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

function addJob(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/mapping-user/job-position/create",
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
          detail: { title: "<span class='text-green-700'>Add Job position</span>", msg: `Success add <b>Job ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Add Job position`, `Success <b>add job position</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else if (json.data.code !== undefined && json.data.code === 3) {
        window.dispatchEvent(new CustomEvent('create-code-exist', { detail: json.data.msg }) );
      } else if (json.data.code !== undefined && json.data.code === 1) {
        window.dispatchEvent(new CustomEvent('create-name-exist', { detail: json.data.msg }) );
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Add Job position`, `Failed <b>add job position</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Add Job`, `Failed <b>add job position</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function updateJob(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/mapping-user/job-position/update",
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
          const jobStore = Alpine.store('job');
          let _data = _.cloneDeep(jobStore.data);
          _data = _.filter(_data, (v, k) => {
            return v.id!==param.id;
          });
          jobStore.setData(_data);
          filterData();
          window.dispatchEvent(new CustomEvent('add-notif', {
            detail: { title: "<span class='text-red-700'>Update Job Position</span>", msg: `Success update  <b>Job Position ${param.id} - ${param.name}</b>`, timeout:5000 }
          }));
          // const notifStore = Alpine.store('notif_modal');
          // notifStore.show(`Remove Job Position`, `Success <b>update  position</b>`, "", "success", `CLOSE`, () => {
          //   notifStore.hide();
          // });
        } else {
          const notifStore = Alpine.store('notif_modal');
          notifStore.show(`Update Job Position`, `Failed <b>update  position</b>, please try again`, "", "failed", `CLOSE`, () => {
            notifStore.hide();
          });
        }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Job Position`, `Failed <b>update  position</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeStatus(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/mapping-user/job-position/updatestatus",
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
          detail: { title: "<span class='text-indigo-700'>Update Job Position Status</span>", msg: `Success update <b>Job Position ${param.id} - ${param.name}</b> status`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update Job Position`, `Success <b>update job position</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update Job Position Status`, `Failed <b>update job position status</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update Job Position Status`, `Failed <b>update job position status</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function removeJob(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/mapping-user/job-position/delete",
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
        const jobStore = Alpine.store('job');
        let _data = _.cloneDeep(jobStore.data);
        _data = _.filter(_data, (v, k) => {
          return v.id!==param.id;
        });
        jobStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { title: "<span class='text-red-700'>Remove Job Position</span>", msg: `Success remove  <b>Job Position ${param.id} - ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Job Position`, `Success <b>remove job</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Job Position`, `Failed <b>remove module</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove Job Position`, `Failed <b>remove module</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
}

function removeMultiJob(id) {
  // console.log(id);
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/mapping-user/job-position/deletemulti",
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
        const jobStore = Alpine.store('job');
        let _data = _.cloneDeep(jobStore.data);
        _data = _.filter(_data, (v, k) => {
          return !_.includes(id, v.id);
        });
        jobStore.setData(_data);
        filterData();
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { title: "<span class='text-red-700'>Remove Job Position</span>", msg: `Success <b>remove selected job</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove Job Position`, `Success <b>remove job</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove Job Position`, `Failed <b>remove job</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove Job Position`, `Failed <b>remove job</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};