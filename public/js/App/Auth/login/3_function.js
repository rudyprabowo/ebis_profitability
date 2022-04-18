const loader = {
  showMainLoader: () => {
    const loaderStore = Alpine.store('loader');
    loaderStore.main = true;
  },
  hideMainLoader: () => {
    const loaderStore = Alpine.store('loader');
    // console.log(loaderStore);
    loaderStore.main = false;
    // console.log(loaderStore);
  }
};

