<?php
use GIG\Presentation\View\ViewHelper;
?>

<div class="wrapper menu-holder">
    <nav class="topnav" id="mainMenu">
        <form name="bottommenu" class="menu-container" id="b-container">
            <?= ViewHelper::menu('bottom') ?>
        </form>
    </nav>
</div>

