<!-- Main Footer -->
  <footer class="no-print text-center text-info">
    <!-- To the right -->
    <!-- <div class="pull-right hidden-xs">
      Anything you want
    </div> -->
    <!-- Default to the left -->
    <small>
    	<b>{{ config('app.name', 'ultimatePOS') }} - V{{config('author.app_version')}} | Copyright &copy; {{ date('Y') }} All rights reserved. Powered By <a href="https://www.linkedin.com/in/afrasiyab-haider-8bab20135/" target="_blank">Afrasiyab Haider</a></b>
    </small>
</footer>

<script type="text/javascript">
	function openPopupWindow(url)
	{
		// var link = 'http://macaobe.com/'+url;
		var link = '{{url('/')}}'+url;
		window.open(link, "_blank", "toolbar=yes,scrollbars=yes,resizable=yes,top=500,left=150,width=1200,height=800");
	}
</script>