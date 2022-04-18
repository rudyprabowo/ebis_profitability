document.addEventListener('DOMContentLoaded', function (event) {
  let tom_select = document.querySelectorAll("select.tom-select-search");
  // console.log(tom_select);
  let tom_tags = document.querySelectorAll("input.tom-input-tag");

  for (let idx = 0; idx < tom_select.length; idx++) {
    if (tom_select[idx].id !== undefined && tom_select[idx].id !== "") {
      tomsel_dom[tom_select[idx].id] = tom_select[idx];

      if (tom_select[idx].id === "create-spv") {
        tomsel[tom_select[idx].id] = new TomSelect(tom_select[idx], {
          persist: true,
          // maxOptions: 3,
          hideSelected: true,
          plugins: {
            'clear_button': {},
            'dropdown_input': {},
            'change_listener': {}
          },
          // allowEmptyOption: true,
          maxItems:1,
          // fetch remote data
          load: function(query, callback) {

            var url = 'https://api.github.com/search/repositories?q=' + encodeURIComponent(query);
            fetch(url)
              .then(response => response.json())
              .then(json => {
                callback(json.items);
              }).catch(()=>{
                callback();
              });

          },
          // custom rendering functions for options and items
          render: {
            option: function(item, escape) {
              return `<div class="py-2 d-flex">
                    <div class="icon me-3">
                      <img class="img-fluid" src="${item.owner.avatar_url}" />
                    </div>
                    <div>
                      <div class="mb-1">
                        <span class="h4">
                          ${ escape(item.name) }
                        </span>
                        <span class="text-muted">by ${ escape(item.owner.login) }</span>
                      </div>
                      <div class="description">${ escape(item.description) }</div>
                    </div>
                  </div>`;
            },
            item: function(item, escape) {
              return `<div class="py-2 d-flex">
                    <div class="icon me-3">
                      <img class="img-fluid" src="${item.owner.avatar_url}" />
                    </div>
                    <div>
                      <div class="mb-1">
                        <span class="h4">
                          ${ escape(item.name) }
                        </span>
                        <span class="text-muted">by ${ escape(item.owner.login) }</span>
                      </div>
                      <div class="description">${ escape(item.description) }</div>
                    </div>
                  </div>`;
            }
          },
        });
      } else {
        tomsel[tom_select[idx].id] = new TomSelect(tom_select[idx], {
          persist: true,
          // maxOptions: 3,
          hideSelected: true,
          plugins: {
            'clear_button': {},
            'dropdown_input': {},
            'change_listener': {}
          },
          // allowEmptyOption: true,
          maxItems:1
        });
      }
    }
  }
  // console.log([tomsel_dom,tomsel]);

  for (let idx = 0; idx < tom_tags.length; idx++) {
    if (tom_select[idx].id !== undefined && tom_select[idx].id !== "") {
      tomtag_dom[tom_tags[idx].id] = tom_tags[idx];
      tomtag[tom_tags[idx].id] = new TomSelect(tom_tags[idx], {
        plugins: ['change_listener'],
        persist: true,
        createOnBlur: true,
        create: true,
        render: {
          no_results: function (data, escape) {
            return '';
          },
        }
      });
    }
  }

  // console.log(tomsel, tomtag);
});