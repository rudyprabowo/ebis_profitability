const yearMonthPicker = {
    selector: [
        ".year-month"
    ],
    initPicker: function(){
        this.selector.forEach(idx => {
            flatpickr(idx,{
                plugins: [
                    new monthSelectPlugin({
                      shorthand: true, //defaults to false
                      dateFormat: "m-Y", //defaults to "F Y"
                      altFormat: "F Y", //defaults to "F Y"
                    //   theme: "dark" // defaults to "light"
                    })
                ]
            });
        });
    }
};