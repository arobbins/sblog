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
          <div class="author-footer-donations changetip_tipme_button" data-bid="0d7a7432-bf39-4766-9577-eaa43c32fedd" data-uid="34dd6b374d524f88bf9a2dcb0c1912ae"></div>
          <script>(function(document,script,id){var js,r=document.getElementsByTagName(script)[0],protocol=/^http:/.test(document.location)?'http':'https';if(!document.getElementById(id)){js=document.createElement(script);js.id=id;js.src=protocol+'://widgets.changetip.com/public/js/widgets.js';r.parentNode.insertBefore(js,r)}}(document,'script','changetip_w_0'));</script>
        </div>
        <?php the_author_meta('description'); ?>
      </div>
    </footer>
    <?php comments_template('/templates/comments.php'); ?>
  </article>
<?php endwhile; ?>