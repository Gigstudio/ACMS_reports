<?php 
use GIG\Presentation\View\ViewHelper;

defined('_RUNKEY') or die; 
?>

<?= $insert('head') ?>
<body>
	<div class="overall">
		<?= $insert('mainmenu') ?>
		<div class="content">
			<?= $insert('content') ?>
		</div>
		<!-- <div class="wrapper bottom menu-holder"> -->
			<!-- <?= $insert('bottommenu') ?> -->
		<!-- </div> -->
		<?= ViewHelper::statusbar() ?>
        <div id="modal-root">
            <?= $insert('modals') ?>
        </div>
		<?= $insert('console') ?>
	</div>
	<div id="snackbar"></div>
	<?= ViewHelper::scripts() ?>
</body>
</html>
