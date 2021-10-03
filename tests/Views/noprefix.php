<?php
/**
 * @var string $name
 * @var Framework\MVC\View $view
 */
$view->extendsWithoutPrefix('layouts/default');
$view->block('contents');
?>
    <div>CONTENTS - <?= $name ?></div>
<?php
$view->endBlock();
$view->block('scripts');
?>
    <script>
        console.log('Oi')
    </script>
<?php
$view->endBlock();
