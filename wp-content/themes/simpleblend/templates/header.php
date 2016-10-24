<?php

use Roots\Sage\Config;
use Roots\Sage\Wrapper;

?>

<?php if( is_front_page() ) { ?>
  <header class="l-row l-center l-space-between header" role="banner">

    <?php echo get_template_part('components/promo/promo', 'controller'); ?>
    <?php echo get_template_part('components/separator/separator', 'controller'); ?>

  </header>
<?php } ?>
