<?php
/**
 * @var Framework\MVC\View $view
 */
$view->extends('layouts/default', 'contents');
?>
    <strong>extends and open block</strong>
<?php
$view->block('scripts');
echo 'Foo';
$view->endBlock();
