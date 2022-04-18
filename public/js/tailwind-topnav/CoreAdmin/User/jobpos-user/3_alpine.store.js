document.addEventListener('alpine:init', () => {
    Alpine.store('jobpos', {
      data: [],
      current_data: [],
      setData(data) {
        this.data = data;
      },
      getSelectedJobpos() {
        let _data = _.cloneDeep(this.current_data);
        _data = _.filter(_data, 'checked');
        return _data;
      },
      getCurrentDataByIndex(idx) {
        return this.current_data[idx];
      },
      setCurrentDataByIndex(idx,data) {
        this.current_data[idx] = data;
      },
      filterData(param,print = false) {
        const mainLoader = Alpine.store('loader');
        mainLoader.show("refreshing table...");
        // console.table(param);
        let _data = _.cloneDeep(this.data);
        /* ------------------------------ Filter Data ------------------------------ */
        _.forEach(param.filter,(val, idx) => {
          // console.log(val);
          let _cond = val.cond;
          let _val = val.val;
          _data = _.filter(_data, row => {
            // console.log({ row, idx, _cond, _val });
            let ret = filteringData(row, idx, _cond, _val);
            // console.log(ret);
            return ret;
          });
        });
        // console.log(_data);
        /* ------------------------------ Sorting Data ----------------------------- */
        _data = _.orderBy(_data, param.sorting.field, param.sorting.order);
  
        if (print ) {
          // console.log(_data);
          if (_data.length > 0) {
            var head = { header: _.keys(_data[0])};
            var filename = "jobpos.xlsx";
  
            const jobposStore = Alpine.store('jobpos');
            var ws_name = "data";
  
            var wb = XLSX.utils.book_new(), ws = XLSX.utils.json_to_sheet(_data, head);
  
            /* add worksheet to workbook */
            XLSX.utils.book_append_sheet(wb, ws, ws_name);
  
            /* write workbook */
            XLSX.writeFile(wb, filename);
          } else {
            window.dispatchEvent(new CustomEvent('add-notif', {
              detail: { type:"failed", title: "Empty Data", msg: `There is no data`, timeout:5000 }
            }));
          }
        } else {
          const pagingStore = Alpine.store('paging');
          pagingStore.data.total_data = _data.length;
          pagingStore.data.total_page = _.ceil(_data.length / param.limit);
          if (pagingStore.data.current_page > pagingStore.data.total_page) {
            pagingStore.data.current_page = pagingStore.data.total_page;
          } else if (pagingStore.data.current_page === 0 && pagingStore.data.total_page > 0) {
            pagingStore.data.current_page = 1;
            param.current_page = 1;
          }
          let start = (param.current_page - 1) * param.limit;
          let end = (param.current_page * param.limit);
          _data = _.slice(_data, start, end);
          // console.table(_data);
          this.current_data = _data;
          // window.dispatchEvent( new CustomEvent('refresh-table') );
        }
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