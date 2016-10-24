<?php

  //
  // Post content
  //

?>

<?php while (have_posts()) : the_post(); ?>

  <article <?php post_class(); ?>>

    <!-- Header -->
    <header class="header">
      <h1 class="entry-title"><?php the_title(); ?></h1>
      <?php get_template_part('templates/entry-meta'); ?>
    </header>

    <!-- Content -->
    <div class="entry-content">

      <?php
      $i = 1;

      if( have_rows('citations') ):


          while ( have_rows('citations') ) : the_row(); ?>

              <aside class="citation" data-citation="<?php echo $i; ?>"><div class="citation-ref"><?php echo $i; ?></div> <div class="citation-content"><?php the_sub_field('citation'); ?></div></aside>

          <?php $i++; endwhile;

      else :

          // no rows found

      endif;

      ?>

      <?php the_content(); ?>
    </div>

    <!-- Footer -->
    <footer class="author-footer l-row">

      <?php echo get_template_part('components/separator/separator', 'controller'); ?>
      <?php echo get_template_part('components/promo/promo', 'controller'); ?>

    </footer>


  </article>

<div class="comments-wrapper">
  <?php comments_template('/templates/comments.php'); ?>
</div>

<?php endwhile; ?>
