<!-- using $datacopy -->
<h2>{{headmain}}</h2>
<div class="menu-container ribbon">
    <?php foreach ($servers as $key => $info){ ?>
        <div class="server-container">
            <h4 class="server-name dropdown"><?=$key?></h4>
            <p class="stats collapsed">
                <?php foreach ($info as $name => $value){ ?>
                    <span class="property-name"><?=$name?>:</span>
                    <span class="property-value <?=$value?>"><?=$value?></span><br>
                <?php } ?>
            </p>
        </div>
    <?php }?>
</div>
<?php
// var_dump($servers);
?>
