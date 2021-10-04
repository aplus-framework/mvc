<?php
/**
 * @var string $name
 * @var Framework\MVC\View $view
 * @var PHPUnit\Framework\TestCase $testCase
 */
$testCase::assertFalse($view->isExtending('default'));
$testCase::assertFalse($view->hasBlock('contents'));
$view->extends('default');
$testCase::assertTrue($view->isExtending('default'));
$view->block('contents');
$testCase::assertFalse($view->hasBlock('contents'));
$testCase::assertFalse($view->inBlock('foo'));
$testCase::assertTrue($view->inBlock('contents'));
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
