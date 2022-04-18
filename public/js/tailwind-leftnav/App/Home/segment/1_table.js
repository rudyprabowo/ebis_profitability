const custTable = {
    dom: null,
    perPage:10,
    curPage:1,
    filter:null,
    dataTable: null,
    initTable:function(){
        const me = this;
        this.dataTable = new simpleDatatables.DataTable('#cust_table', {
            perPage: me.perPage,
            searchable: false,
            fixedHeight: true,
            sortable: false,
            columns: [
                { 
                    select: 1, 
                    render: function(data, cell, row){
                        new Intl.NumberFormat(['id']).format(data);
                    } 
                },
                { 
                    select: 2, 
                    render: function(data, cell, row){
                        new Intl.NumberFormat(['id']).format(data);
                    } 
                },
                {
                    select: 0,
                    render: function(data, cell, row) {
                        return `<button @click="window.scrollTo({ top: 0, behavior: 'smooth' });show_preview=true;$nextTick(() => { $dispatch('show-mainmodal', true) })" type='button' data-cust_id='1'>${data}</button>`;
                    }
                }
            ],
            layout: {
                top: "",
                bottom: "{pager}"
            },
            data: {
                "headings": [
                  "Customer",
                  "Revenue (Rp M)",
                  "Margin",
                  "Profitability"
                ],
                "data": [
                    [
                        "Customer A",
                        1000000,
                        200000,
                        "20%"
                    ]
                ]
            }
        });
    },
}