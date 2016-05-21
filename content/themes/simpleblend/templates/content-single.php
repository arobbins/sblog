<?php

  //
  // Post content
  //

?>

<?php while (have_posts()) : the_post(); ?>

  <article <?php post_class(); ?>>
    <header>
      <h1 class="entry-title"><?php the_title(); ?></h1>
      <?php get_template_part('templates/entry-meta'); ?>
    </header>
    <div class="entry-content">
      <?php the_content(); ?>
    </div>
    <footer class="author-footer l-row">
      <?php wp_link_pages(['before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']); ?>
      <div class="l-grid-5 author-footer-avatar">
        <?php
          $author_id = get_the_author_meta('ID');
        ?>
        <?php echo get_avatar(1, 300); ?>
      </div>
      <div class="l-fit l-row-left author-footer-bio l-row">
        <span class="author-footer-name">About myself</span>
        <div class="author-contact">
          <p class="author-footer-social"><a href="https://twitter.com/andrewmrobbins" class="twitter-follow-button" data-show-count="false" data-size="large" data-show-screen-name="true">Follow @andrewmrobbins</a></p>
        </div>
        <?php the_author_meta('description'); ?>
      </div>
    </footer>
    <?php comments_template('/templates/comments.php'); ?>
  </article>

<?php endwhile; ?>
