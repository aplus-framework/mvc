<?php
/**
 * @var Framework\MVC\View $view
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $title ?? 'Title' ?></title>
</head>
<body>
<?= $view->renderBlock('contents') ?>
<?= $view->renderBlock('scripts') ?>
</body>
</html>
