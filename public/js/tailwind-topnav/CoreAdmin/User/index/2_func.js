function reloadData(dbcache = 1, params = {}) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("retrieve data...");
  fetchWithTimeout(
    "/core-admin/manage-user/getall?dbcache="+dbcache,
    {
      // mode: "no-cors",
      // redirect: "follow",
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
      },
      body: JSON.stringify(params)
    },
    10000
  )
    .then((response) => response.json())
    .then((json) => {
      if (json.ret) {
        // console.table(json.data);
        const userStore = Alpine.store('user');
        userStore.setData(json.data._data);

        window.dispatchEvent(new CustomEvent('has-next', {
          detail: json.data.hasNext
        }));
      }
      mainLoader.hide();
      // filterData();
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

function filterData(dbcache=1, print = false) {
  const filterStore = Alpine.store('filter');
  let filter = _.cloneDeep(filterStore.data);
  filter = _.pickBy(filter, (val, idx) => {
    return val.cond !== "none" && val.val !== null;
  });
  const sortingStore = Alpine.store('sorting');
  let sorting = _.cloneDeep(sortingStore.data);
  const pagingStore = Alpine.store('paging');
  let { limit, current_page } = _.cloneDeep(pagingStore.data);
  console.log({ filter, sorting, limit, current_page });
  if (sorting.field === null || sorting.order === "none") sorting = {};
  reloadData(dbcache, { filter, sorting, limit, current_page });

  // const userStore = Alpine.store('user');
  // userStore.filterData({ filter, sorting, limit, current_page }, print);
}

function addUser(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-user/create",
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
          detail: { type:"success", title: "Add User", msg: `Success add <b>User ${param.name}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Add User`, `Success <b>add user</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      // } else if (json.data.code !== undefined && json.data.code === 1) {
      //   window.dispatchEvent(new CustomEvent('create-username-exist', { detail: json.data.msg }) );
      // } else if (json.data.code !== undefined && json.data.code === 2) {
      //   window.dispatchEvent(new CustomEvent('create-email-exist', { detail: json.data.msg }) );
      // } else if (json.data.code !== undefined && json.data.code === 3) {
      //   window.dispatchEvent(new CustomEvent('create-nik-exist', { detail: json.data.msg }) );
      // } else if (json.data.code !== undefined && json.data.code === 4) {
      //   window.dispatchEvent(new CustomEvent('create-mobile-exist', { detail: json.data.msg }) );
      // }  else if (json.data.code !== undefined && json.data.code === 5) {
      //   window.dispatchEvent(new CustomEvent('create-telegram-exist', { detail: json.data.msg }) );
      }  else if (json.data.code !== undefined && json.data.code === 1) {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Add User`, `Failed <b>add user</b>, user with similar data (username, email, nik, mobile no, telegram id) has been exist`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Add User`, `Failed <b>add user</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Add User`, `Failed <b>add user</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeStatus(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-user/updatestatus",
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
          detail: { type:"success", title: "Update User Status", msg: `Success update <b>User ${param.id} - ${param.username}</b> status`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update User`, `Success <b>update user</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update User Status`, `Failed <b>update user status</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update User Status`, `Failed <b>update user status</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function changeOrganic(param,el,old_val) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-user/updateorganic",
    {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-type': 'application/json'
      },
      body: JSON.stringify({ id: param.id, is_organic: !old_val })
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
          detail: { type:"success", title: "Update User Organic Flag", msg: `Success update <b>User ${param.id} - ${param.username}</b> Organic Flag`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Update User`, `Success <b>update user</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Update User Organic Flag`, `Failed <b>update user Organic Flag</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Update User Organic Flag`, `Failed <b>update user Organic Flag</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};

function removeUser(param) {
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-user/delete",
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
        filterData(0, false);
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { type:"success", title: "Remove User", msg: `Success remove  <b>User ${param.id} - ${param.username}</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove User`, `Success <b>remove user</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove User`, `Failed <b>remove user</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove User`, `Failed <b>remove user</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
}

function removeMultiUser(id) {
  // console.log(id);
  const mainLoader = Alpine.store('loader');
  mainLoader.show("sending request...");
  fetchWithTimeout(
    "/core-admin/manage-user/deletemulti",
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
        filterData(0, false);
        window.dispatchEvent(new CustomEvent('add-notif', {
          detail: { type:"success", title: "Remove User", msg: `Success <b>remove selected user</b>`, timeout:5000 }
        }));
        // const notifStore = Alpine.store('notif_modal');
        // notifStore.show(`Remove User`, `Success <b>remove user</b>`, "", "success", `CLOSE`, () => {
        //   notifStore.hide();
        // });
      } else {
        const notifStore = Alpine.store('notif_modal');
        notifStore.show(`Remove User`, `Failed <b>remove user</b>, please try again`, "", "failed", `CLOSE`, () => {
          notifStore.hide();
        });
      }
    })
    .catch((error) => {
      console.error(error.message);
      mainLoader.hide();
      const notifStore = Alpine.store('notif_modal');
      notifStore.show(`Remove User`, `Failed <b>remove user</b>, please try again`, "", "failed", `CLOSE`, () => {
        notifStore.hide();
      });
    });
};