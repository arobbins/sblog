<?php foreach(posts_by_year() as $year => $posts) : ?>
  <h2 class="article-year"><?php echo $year; ?></h2>
  <ol class="articles">
    <?php foreach($posts as $post) : setup_postdata($post); ?>
      <li class="article">
        <a href="<?php the_permalink(); ?>" class="article-link"><?php the_title(); ?></a>
        <?php the_tags(); ?>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endforeach; ?>

<?php // the_posts_navigation(); ?>
