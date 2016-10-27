<section class="form-wrapper l-center">
  <p>Subscribe to my mailing list to receive future content and special offers <small>(I will never send spam or sell your email address to third-parties)</small></p>

  <form id="mailinglist-form" class="formee" action="<?php echo $file; ?>" method="post" data-nonce="<?php echo wp_create_nonce('mailinglist'); ?>">

    <div class="form-control l-row">
      <input name="email" id="mailinglist-email" type="text" class="form-input" placeholder="Email address" />
      <?php wp_nonce_field('mailinglist_signup'); ?>
      <input class="btn" type="submit" title="Sign up" value="Sign up" />
      <div class="spinner"></div>
    </div>

    <aside class="mailinglist-messages">
      <div class="mailinglist-error"></div>
      <div class="mailinglist-success"></div>
    </aside>

  </form>


</section>
