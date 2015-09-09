<aside class="l-row article-info">
  <time class="article-date" datetime="<?= get_post_time('c', true); ?>"><?= get_the_date(); ?></time> <i class="fa fa-diamond post-divider hvr-wobble-vertical"></i> <?php the_author(); ?> <i class="fa fa-diamond post-divider hvr-wobble-vertical"></i> <a href="<?php the_permalink(); ?>#disqus_thread" class="article-comments"> Comments</a></p>
</aside>