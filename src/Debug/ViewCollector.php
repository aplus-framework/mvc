<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC\Debug;

use Framework\Debug\Collector;
use Framework\MVC\View;

/**
 * Class ViewCollector.
 *
 * @package mvc
 */
class ViewCollector extends Collector
{
    protected View $view;

    public function setView(View $view) : static
    {
        $this->view = $view;
        return $this;
    }

    public function getContents() : string
    {
        if ( ! isset($this->view)) {
            return '<p>A View instance has not been set on this collector.</p>';
        }
        \ob_start(); ?>
        <h1>Rendered Views</h1>
        <?php
        echo $this->renderRenderedViews();
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderRenderedViews() : string
    {
        if ( ! $this->hasData()) {
            return '<p>No view has been rendered.</p>';
        }
        \ob_start();
        $count = \count($this->getData()); ?>
        <p>Total of <?= $count ?> rendered view file<?= $count > 1 ? 's' : '' ?>.</p>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>File</th>
                <th>Type</th>
                <th>Time to Render</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->getData() as $index => $data): ?>
                <tr title="<?= \htmlentities($data['filepath']) ?>">
                    <td><?= $index + 1 ?></td>
                    <td><?= \htmlentities($data['file']) ?></td>
                    <td><?= \htmlentities($data['type']) ?></td>
                    <td><?= \round($data['end'] - $data['start'], 6) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }
}
