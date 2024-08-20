<?php
/**
 * @var Framework\MVC\View $view
 */
$view->extends('_layouts/default', 'contents');
?>
<h2>Home Index</h2>
<div>Foo bar baz</div>
<div>
    <?= $view->include('_includes/footer') ?>
</div>
<div>
    <?= $view->include('_includes/footer') ?>
</div>
