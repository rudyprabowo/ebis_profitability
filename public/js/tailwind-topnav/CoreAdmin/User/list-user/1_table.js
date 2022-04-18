let elements = document.querySelectorAll("[data-menu]");
for (let i = 0; i < elements.length; i++) {
    let main = elements[i];
    main.addEventListener("click", function () {
        let element = main.parentElement.parentElement;
        let andicators = main.querySelectorAll("svg");
        let child = element.querySelector("ul");
        // console.log(element);
        if (child.classList.contains("opacity-0")) {
            child.classList.remove("invisible");
            child.classList.add("visible");
            child.classList.add("opacity-100");
            child.classList.remove("opacity-0");
            andicators[0].style.display = "block";
            andicators[1].style.display = "none";
        } else {
            child.classList.add("invisible");
            child.classList.remove("visible");
            child.classList.remove("opacity-100");
            child.classList.add("opacity-0");
            andicators[0].style.display = "none";
            andicators[1].style.display = "block";
        }
    });
}
var tableDetails = document.getElementsByClassName("detail-row");
for (var i = 0; i < tableDetails.length; i++) {
    tableDetails[i].getElementsByTagName("td")[0].classList.add("hidden");
}

function dropdownFunction(element) {
    // console.log(element);
    var single = element.getElementsByClassName("dropdown-content")[0];
    single.classList.toggle("hidden");
}

function convertStatus(status) {
  switch (status) {
    case 0:
      return 'Not Active';
    case 1:
      return 'Active';
    case 3:
      return 'Blocked';
    default:
      return 'Unknown';
  }
}

document.addEventListener('alpine:initialized', () => {
  const tblStore = Alpine.store('tbl');
  // tblStore.data = tblData;
  tblStore.reloadData();

  const moduleStore = Alpine.store('module');
  // console.log(moduleData);
  moduleStore.data = moduleData;
  // let moduleReactive = Alpine.reactive(moduleData);

  const layoutStore = Alpine.store('layout');
  layoutStore.data = layoutData;

  const menuStore = Alpine.store('menu');
  menuStore.data = menuData;
});