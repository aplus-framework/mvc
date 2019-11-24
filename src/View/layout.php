<?php
/**
 * @var string                 $content
 * @var string                 $title
 * @var \Framework\Theme\Theme $theme
 * @var array                  $breadcrumb
 * @var int                    $wishlistCount
 */
?>
<!doctype html>
<html lang="<?= $theme->getLang() ?>">
<head>
	<?= $theme->renderMetas() ?>
	<?= $theme->renderStyles() ?>
	<?= $theme->renderTitle() ?>
</head>
<body>
<?= $content ?>
<?= $theme->renderScripts() ?>
</body>
</html>
