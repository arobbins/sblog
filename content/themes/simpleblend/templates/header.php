<?php if(is_front_page()) { ?>

<?php } else { ?>

<header class="l-row l-col-center header" role="banner">
  <a class="l-fit header-back-link fa fa-long-arrow-left hvr-wobble-horizontal" href="<?= esc_url(home_url('/')); ?>">
    <!-- <img src="<?php echo get_template_directory_uri(); ?>/assets/imgs/logo-mark-dark.svg" alt="Simpleblend Blog" class="header-logo"> -->
  </a>
  <ul class="l-grid-3 l-row-end l-row header-nav">
    <!-- <li class="header-nav-item">
      <a href="https://www.changetip.com/tipme/andrewmrobbins" class="header-nav-link header-nav-link-bitcoin" target="_blank"> <i class="fa fa-bitcoin"></i> </a>
    </li> -->

    <!-- <li class="header-nav-item">
      <a href="https://twitter.com/andrewmrobbins" class="header-nav-link"> <i class="fa fa-twitter"></i> </a>
    </li>
    -->
  </ul>
</header>

<?php } ?>