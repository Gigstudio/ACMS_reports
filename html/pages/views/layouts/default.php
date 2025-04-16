<?php 
use GigReportServer\System\Engine\ViewHelper;

defined('_RUNKEY') or die; 
?>

<?= $insert('head') ?>
<body>
	<div class="overall">
		<?= $insert('mainmenu') ?>
		<div class="content">
			<?= $insert('content') ?>
		</div>
		<div class="wrapper bottom menu-holder">
			<?= $insert('bottommenu') ?>
		</div>
		<?= $insert('console') ?>
	</div>
	<?= ViewHelper::scripts() ?>
</body>
</html>
