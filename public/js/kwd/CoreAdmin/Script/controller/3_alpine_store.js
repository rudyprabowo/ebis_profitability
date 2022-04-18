document.addEventListener('alpine:init', () => {
  Alpine.store('controller', {
    data: [],
    current_data: [],
    setData(data) {
      this.data = data;
    },
    getSelectedController() {
      let _data = _.cloneDeep(this.current_data);
      _data = _.filter(_data, 'checked');
      return _data;
    },
    getCurrentDataByIndex(idx) {
      return this.current_data[idx];
    },
    setCurrentDataByIndex(idx, data) {
      this.current_data[idx] = data;
    },
    filterData(param) {
      const mainLoader = Alpine.store('loader');
      mainLoader.show("refreshing table...");
      // console.table(param);
      let _data = _.cloneDeep(this.data);
      _.forEach(param.filter, (val, idx) => {
        //   console.log(val);
        let _cond = val.cond;
        let _val = val.val;
        _data = _.filter(_data, row => {
          // console.log({ row, idx, _cond, _val });
          let ret = filteringData(row, idx, _cond, _val);
          console.log(ret);
          return ret;
        });
      });
      // console.log(_data);
      _data = _.orderBy(_data, param.sorting.field, param.sorting.order);

      const pagingStore = Alpine.store('paging');
      pagingStore.data.total_data = _data.length;
      pagingStore.data.total_page = _.ceil(_data.length / param.limit);
      if (pagingStore.data.current_page > pagingStore.data.total_page) {
        pagingStore.data.current_page = pagingStore.data.total_page;
      }
      let start = (param.current_page - 1) * param.limit;
      let end = (param.current_page * param.limit) - 1;
      _data = _.slice(_data, start, end);
      // console.table(_data);
      this.current_data = _data;
      // window.dispatchEvent( new CustomEvent('refresh-table') );
      mainLoader.hide();
    },
    changeCheck(idx, checked) {
      // console.log(this.current_data[idx], idx, checked);
      if (this.current_data[idx] !== undefined) {
        this.current_data[idx].checked = checked;
      }
    },
    checkAll(checked) {
      this.current_data.map((v, k) => {
        // this.current_data[k].checked = checked;
        // this.changeCheck(k, checked);
        if (v.checked !== checked) {
          document.getElementById('checkrow_' + k).click();
        }
      });
      // console.log(this.current_data);
    }
  });

  Alpine.store('filter', {
    data: {},
    setData(data) {
      this.data = data;
    },
  });

  Alpine.store('sorting', {
    data: {
      field: null,
      order: "none"
    },
    setData(data) {
      this.data = data;
    },
  });

  Alpine.store('paging', {
    data: {
      limit: 10,
      total_page: 0,
      total_data: 0,
      current_page: 1
    }
  });
});