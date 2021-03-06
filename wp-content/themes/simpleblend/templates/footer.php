<footer class="footer content-info l-col l-row-center" role="contentinfo">

    <?php echo get_template_part('components/separator/separator', 'controller'); ?>
    <?php echo get_template_part('components/mailinglist/mailinglist', 'controller'); ?>

    <p><span class="name">Andrew Robbins</span> <span class="title">Web Developer</span></p>
    <p><a href="https://simpleblend.net">Simpleblend.net</a></p>
    <p>
      <a href="<?php the_field('theme_twitter', 'option');?>" class="social-link"><i class="fa fa-twitter" aria-hidden="true"></i></a>
      <a href="<?php the_field('theme_instagram', 'option');?>" class="social-link"><i class="fa fa-instagram" aria-hidden="true"></i></a>
      <a href="<?php the_field('theme_github', 'option');?>" class="social-link"><i class="fa fa-github" aria-hidden="true"></i></a>
      <a href="<?php the_field('theme_linkedin', 'option');?>" class="social-link"><i class="fa fa-linkedin" aria-hidden="true"></i></a>
    </p>

</footer>

<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>

<script type="text/javascript">
    /* * * CONFIGURATION VARIABLES * * */
    var disqus_shortname = 'simpleblend';

    /* * * DON'T EDIT BELOW THIS LINE * * */
    (function () {
        var s = document.createElement('script'); s.async = true;
        s.type = 'text/javascript';
        s.src = '//' + disqus_shortname + '.disqus.com/count.js';
        (document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(s);
    }());
</script>

<script>
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-57791901-1']);
  _gaq.push(['_setDomainName', 'simpleblend.net']);
  _gaq.push(['_trackPageview']);
</script>
