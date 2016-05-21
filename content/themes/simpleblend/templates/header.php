<?php

use Roots\Sage\Config;
use Roots\Sage\Wrapper;

?>
<header class="l-row l-center l-space-between header" role="banner">
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo-link">
    <img src="<?php the_field('theme_logo_dark', 'option'); ?>" alt="Simpleblend Blog" class="logo" />
  </a>
</header>
