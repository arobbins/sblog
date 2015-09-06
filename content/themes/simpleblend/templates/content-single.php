<?php while (have_posts()) : the_post(); ?>
  <article <?php post_class(); ?>>
    <header>
      <h1 class="entry-title"><?php the_title(); ?></h1>
      <?php get_template_part('templates/entry-meta'); ?>
    </header>
    <div class="entry-content">
      <?php the_content(); ?>
    </div>
    <footer class="article-author l-row">
      <?php wp_link_pages(['before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']); ?>
      <div class="l-grid-5 author-image">
        <?php
          $author_id = get_the_author_meta('ID');
        ?>
        <img src="<?php the_field('author_image', 'user_'. $author_id) ?>" alt="">
      </div>
      <div class="l-fit author-bio l-row">
        <div class="l-grid-4">
          <span class="author-name"><?= get_the_author(); ?></span>
        </div>
        <div class="l-fit">
          <p class="author-name"><a href="https://twitter.com/andrewmrobbins" class="twitter-follow-button" data-show-count="false" data-size="large">Follow @andrewmrobbins</a></p>
        </div>
        <p><?php the_author_meta('description'); ?></p>
      </div>
    </footer>
    <?php comments_template('/templates/comments.php'); ?>
  </article>
<?php endwhile; ?>