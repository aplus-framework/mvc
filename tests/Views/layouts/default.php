<?php
/**
 * @var \Framework\MVC\View $this
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title><?= $title ?? 'Title' ?></title>
</head>
<body>
<?= $this->renderSection('contents') ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
