<?php
use GIG\Presentation\View\ViewHelper;

defined('_RUNKEY') or die; 
?>

<!DOCTYPE html>
<html lang="<?= ViewHelper::config('application.default_locale', 'en') ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= ViewHelper::config('application.default_charset', 'utf-8') ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="Description" content="<?= ViewHelper::config('application.description') ?>">
    <title><?= ViewHelper::config('application.short_name') ?></title>
    <link rel="shortcut icon" href="<?= ViewHelper::config('application.favicon') ?>" type="image/png"/>

    <?= ViewHelper::fonts() ?>
    <?= ViewHelper::styles() ?>
</head>
