<?php
/**
 * @var Framework\MVC\View $view
 */
$view->extendsWithoutPrefix('_layouts/default');
$view->block('contents');
?>
<h2>Home Index</h2>
<div>Foo bar baz</div>
<?= $view->includeWithoutPrefix('_includes/footer') ?>
<?php
$view->endBlock();
/*$view->block('footer');
echo $view->include('footer');
$view->endBlock();*/
