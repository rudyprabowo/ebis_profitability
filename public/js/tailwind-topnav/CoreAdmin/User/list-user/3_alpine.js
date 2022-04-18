document.addEventListener('alpine:init', () => {
    Alpine.store('filter', {
      field: {
        id: {
          condition: "none",
          label: "USER ID",
          value: "",
          type: 'text',
          order: "none",
          order_priority:null
        },
        name: {
          condition: "none",
          label: "USER NAME",
          value: "",
          type: 'text',
          order: "none",
          order_priority:null
        },
        created_date: {
          condition: "none",
          label: "FULL NAME",
          value: "",
          type: 'text',
          order: "none",
          order_priority:null
        },
        updated_date: {
          condition: "none",
          label: "EMAIL",
          value: "",
          type: 'text',
          order: "none",
          order_priority:null
        },
        status: {
          condition: "none",
          label: "STATUS",
          value: "none",
          type: 'select',
          select: [
            {
              val: 1,
              label: 'Active'
            },
            {
              val: 0,
              label: 'Not Active'
            }
          ],
          order: "none",
          order_priority:null
        },
      },
      condition: false,
      sorting: false
    });
  
    Alpine.store('create_rule', {
      show: false,
    });
  });
  
  document.addEventListener('alpine:initialized', () => {
    // const ruleStore = Alpine.store('rule');
    // ruleStore.initData(rule_data);
  });