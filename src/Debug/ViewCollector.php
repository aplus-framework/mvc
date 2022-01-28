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

    public function getActivities() : array
    {
        $activities = [];
        foreach ($this->getData() as $data) {
            $activities[] = [
                'collector' => $this->getName(),
                'class' => static::class,
                'description' => 'Render view ' . $data['file'],
                'start' => $data['start'],
                'end' => $data['end'],
            ];
        }
        return $activities;
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
        $data = $this->getData();
        \usort($data, static function ($d1, $d2) {
            return $d1['start'] <=> $d2['start'];
        });
        \ob_start();
        $count = \count($data); ?>
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
            <?php foreach ($data as $index => $item): ?>
                <tr title="<?= \htmlentities($item['filepath']) ?>">
                    <td><?= $index + 1 ?></td>
                    <td><?= \htmlentities($item['file']) ?></td>
                    <td><?= \htmlentities($item['type']) ?></td>
                    <td><?= \round($item['end'] - $item['start'], 6) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }
}
