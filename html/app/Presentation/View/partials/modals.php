<?php
defined('_RUNKEY') or die;
/** @var array $modals */
$modals = $modals ?? [];
foreach ($modals as $modalBlock) {
    if ($modalBlock instanceof \GIG\Core\Block) {
        echo $this->render($modalBlock);
    }
}
?>
