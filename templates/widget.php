<script>
window.belcoConfig = <?php echo json_encode($config); ?>;
</script>
<script>(function(e,t){var n=e.createElement(t);n.async=true;n.src="//cdn.belco.io/widget.js";n.onload=n.onreadystatechange=function(){var e=this.readyState;if(e)if(e!="complete")if(e!="loaded")return;try{Belco("init",belcoConfig)}catch(t){}};var r=e.getElementsByTagName(t)[0];r.parentNode.insertBefore(n,r)})(document,"script")</script>