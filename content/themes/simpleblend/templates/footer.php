
<footer class="footer content-info l-row l-row-center" role="contentinfo">
  <?php if(!is_front_page()) { ?>
    <a class="l-fit header-back-link fa fa-long-arrow-left hvr-wobble-horizontal" href="<?= esc_url(home_url('/')); ?>">

    </a>
  <?php } else { ?>

    <p>Andrew Robbins &mdash; Web Developer</p>
    <p><a href="https://simpleblend.net">https://simpleblend.net</a> &bull; <a href=""><i class="fa fa-twitter" aria-hidden="true"></i></a> <a href="#"><i class="fa fa-linkedin" aria-hidden="true"></i></a>
</p>
  <?php } ?>
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
