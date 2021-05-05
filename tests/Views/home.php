<?php
/**
 * @var string              $name
 * @var \Framework\MVC\View $this
 */
$this->extends('layouts/default');
$this->startSection('contents');
?>
	<div>CONTENTS - <?= $this->escape($name) ?></div>
<?php
$this->endSection();
$this->startSection('scripts');
?>
	<script>
		console.log('Oi')
	</script>
<?php
$this->endSection();
