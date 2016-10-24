<?php foreach(posts_by_year() as $year => $months) { ?>

  <?php foreach($months as $month => $posts) { ?>

    <h3 class="article-date">
      <p class="article-year"><?php echo $year; ?></p>
      <i class="article-divider fa fa-flash" aria-hidden="true"></i>
      <p class="article-month"><?php echo $month; ?></p>
    </h3>

    <ol class="articles">
      <?php foreach($posts as $post) : setup_postdata($post); ?>
        <li class="article">
          <a href="<?php the_permalink(); ?>" class="article-link"><?php the_title(); ?></a>
        </li>
      <?php endforeach; ?>
    </ol>

  <?php }

  }

?>
