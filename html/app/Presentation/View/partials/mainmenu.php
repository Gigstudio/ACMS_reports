<?php
use GIG\Presentation\View\ViewHelper;
?>

<div class="wrapper controls-holder">
    <div class="switch-container">
        <span id="light"><i class="fas fa-sun"></i></span>
        <label class="switch">
            <input type="checkbox" class="command switch-toggle hidden" id="theme-toggle">
            <span class="slider round"></span>
        </label>
        <span id="dark"><i class="fas fa-moon"></i></span>
    </div>
    <div class="switch-container flex-vertical hidden" id="percoSyncLed">
        <span class="sync indicator" id="percoLedDot"></span>
        <span class="sync-label" id="percoLedLabel"></span>
    </div>
</div>
<div class="wrapper menu-holder">
    <nav class="topnav" id="mainMenu">
        <form name="mainmenu" class="menu-container" id="m-container">
            <?= ViewHelper::menu('main') ?>
        </form>
        <div class="menu-container flex-right">
            <a class="command menu-item icon"
                title="Log in"
                id="login"
                data-modal="login-modal"
                data-modal-url="/api/auth/modal">
                <span><i class="fas fa-right-to-bracket"></i></span>
            </a>
        </div>
    </nav>
</div>
