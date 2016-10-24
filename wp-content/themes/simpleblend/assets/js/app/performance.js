function lazyLoadImgs($) {
  $('.is-lazy').lazyload({
    effect: "fadeIn",
    threshold: 100
  });
};

export {
  lazyLoadImgs  
}
