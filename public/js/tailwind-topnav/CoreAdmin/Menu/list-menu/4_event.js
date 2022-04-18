document.addEventListener('DOMContentLoaded', function (event) {
    let tom_select = document.querySelectorAll("select.tom-select-search");
    let tom_tags = document.querySelectorAll("input.tom-input-tag");

    for (let idx = 0; idx < tom_select.length; idx++) {
        if (tom_select[idx].id !== undefined && tom_select[idx].id !== "") {
            tomsel_dom[tom_select[idx].id] = tom_select[idx];
            tomsel[tom_select[idx].id] = new TomSelect(tom_select[idx], {
                persist: true,
                // maxOptions: 3,
                hideSelected: true,
                plugins: {
                    'dropdown_input': {},
                    'change_listener': {}
                }
            });
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

    //select with placeholder
    let tom_select2 = document.querySelectorAll("select.tom-select-search2");
    for (let idx = 0; idx < tom_select2.length; idx++) {
        if (tom_select2[idx].id !== undefined && tom_select2[idx].id !== "") {
            tomsel_dom2[tom_select2[idx].id] = tom_select2[idx][0];
            console.log(tom_select2[idx][0]);
            tomsel2[tom_select2[idx].id] = new TomSelect(tom_select2[idx], {
                items: ["opt1"],
                create: false,
                sortField: { field: "text" }
            });
        }
    }

    //////////////ICON SELECT ////
    let options = [];
    let id = 0;
    for (var i = 0; i < list_icon.length; i++) {
        id++;
        options.push({ "id": id, "title": "ri-" + list_icon[i] + "-line", "url": '<i class="ri-' + list_icon[i] + '-line"></i>' });
        id++;
        options.push({ "id": id, "title": "ri-" + list_icon[i] + "-fill", "url": '<i class="ri-' + list_icon[i] + '-fill"></i>' });
    }

    new TomSelect('#select_icon', {
        maxItems: 1,
        valueField: 'url',
        searchField: 'title',
        options: options,
        render: {
            option: function (data, escape) {
                return '<div>' +
                    '<span class="title">' + escape(data.title) + '</span>' +
                    '<i class="' + escape(data.title) + '"></i>'
                '</div>';
            },
            item: function (data, escape) {
                return '<div title="' + escape(data.title) + '">' + escape(data.title) + '</div>';
            }
        }
    });



});