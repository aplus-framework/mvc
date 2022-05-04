<?php
/**
 * @var Framework\MVC\View $view
 */
$view->extends('default');
$view->block('contents');
?>
<h2>Home Index</h2>
<div>Foo bar baz</div>
<?= $view->include('footer') ?>
<?php
$view->endBlock();
/*$view->block('footer');
echo $view->include('footer');
$view->endBlock();*/
