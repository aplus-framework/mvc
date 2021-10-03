<?php
/**
 * @var string $name
 * @var Framework\MVC\View $view
 * @var PHPUnit\Framework\TestCase $testCase
 */
$testCase::assertFalse($view->isExtending('default'));
$view->extends('default');
$testCase::assertTrue($view->isExtending('default'));
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
