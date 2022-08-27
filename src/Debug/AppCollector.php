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
use Framework\Debug\Debugger;
use Framework\MVC\App;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class AppCollector.
 *
 * @package mvc
 */
class AppCollector extends Collector
{
    protected App $app;
    protected float $startTime;
    protected float $endTime;
    protected int $startMemory;
    protected int $endMemory;

    public function setApp(App $app) : static
    {
        $this->app = $app;
        if ( ! isset($this->startTime)) {
            $this->setStartTime();
        }
        if ( ! isset($this->startMemory)) {
            $this->setStartMemory();
        }
        return $this;
    }

    public function setStartTime(float $microtime = null) : static
    {
        $this->startTime = $microtime ?? \microtime(true);
        return $this;
    }

    public function setEndTime(float $microtime = null) : static
    {
        $this->endTime = $microtime ?? \microtime(true);
        return $this;
    }

    public function setStartMemory(int $memoryUsage = null) : static
    {
        $this->startMemory = $memoryUsage ?? \memory_get_usage();
        return $this;
    }

    public function setEndMemory(int $memoryUsage = null) : static
    {
        $this->endMemory = $memoryUsage ?? \memory_get_usage();
        return $this;
    }

    public function getActivities() : array
    {
        $activities = [];
        $activities[] = [
            'collector' => $this->getName(),
            'class' => static::class,
            'description' => 'Runtime',
            'start' => $this->startTime,
            'end' => $this->endTime,
        ];
        foreach ($this->getServices() as $service => $data) {
            foreach ($data as $item) {
                $activities[] = [
                    'collector' => $this->getName(),
                    'class' => static::class,
                    'description' => 'Load service ' . $service . ':' . $item['name'],
                    'start' => $item['start'],
                    'end' => $item['end'],
                ];
            }
        }
        return $activities;
    }

    public function getContents() : string
    {
        if ( ! isset($this->endTime)) {
            $this->setEndTime(\microtime(true));
        }
        if ( ! isset($this->endMemory)) {
            $this->setEndMemory(\memory_get_usage());
        }
        \ob_start(); ?>
        <p><strong>Started at:</strong> <?= \date('r', (int) $this->startTime) ?></p>
        <p><strong>Runtime:</strong> <?= \round($this->endTime - $this->startTime, 6) ?> seconds
        </p>
        <p>
            <strong>Memory:</strong> <?=
            Debugger::convertSize($this->endMemory - $this->startMemory) ?>
        </p>
        <h1>Services</h1>
        <h2>Loaded Service Instances</h2>
        <?= $this->renderLoadedServices() ?>
        <h2>Available Services</h2>
        <?php
        echo $this->renderAvailableServices();
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderLoadedServices() : string
    {
        $services = $this->getServices();
        $total = 0;
        foreach ($services as $data) {
            $total += \count($data);
        }
        if ($total === 0) {
            return '<p>No service instance has been loaded.</p>';
        }
        \ob_start(); ?>
        <p>Total of <?= $total ?> service instance<?= $total !== 1 ? 's' : '' ?> loaded.</p>
        <table>
            <thead>
            <tr>
                <th>Service</th>
                <th>Instances</th>
                <th title="Seconds">Time to Load</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($services as $service => $data): ?>
                <?php $count = \count($data) ?>
                <tr>
                    <td rowspan="<?= $count ?>"><?= $service ?></td>
                    <td><?= $data[0]['name'] ?></td>
                    <td><?= \round($data[0]['end'] - $data[0]['start'], 6) ?></td>
                </tr>
                <?php for ($i = 1; $i < $count; $i++): ?>
                    <tr>
                        <td><?= $data[$i]['name'] ?></td>
                        <td><?= \round($data[$i]['end'] - $data[$i]['start'], 6) ?></td>
                    </tr>
                <?php endfor ?>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    /**
     * @return array<string,mixed>
     */
    protected function getServices() : array
    {
        $result = [];
        foreach ($this->getData() as $data) {
            if ( ! isset($result[$data['service']])) {
                $result[$data['service']] = [];
            }
            $result[$data['service']][] = [
                'name' => $data['instance'],
                'start' => $data['start'],
                'end' => $data['end'],
            ];
        }
        return $result; // @phpstan-ignore-line
    }

    protected function renderAvailableServices() : string
    {
        \ob_start();
        $services = [];
        $class = new ReflectionClass($this->app);
        $methods = $class->getMethods(ReflectionMethod::IS_STATIC);
        foreach ($methods as $method) {
            if ( ! $method->isPublic()) {
                continue;
            }
            $name = $method->getName();
            if (\in_array($name, [
                'config',
                'getService',
                'setService',
                'removeService',
                'isCli',
                'setIsCli',
                'isDebugging',
            ], true)) {
                continue;
            }
            $param = $method->getParameters()[0] ?? null;
            if ( ! $param || $param->getName() !== 'instance') {
                continue;
            }
            if ($param->getType()?->getName() !== 'string') { // @phpstan-ignore-line
                continue;
            }
            $instances = [];
            if ($param->isDefaultValueAvailable()) {
                $instances[] = $param->getDefaultValue();
            }
            foreach ((array) $this->app::config()->getInstances($name) as $inst => $s) {
                $instances[] = $inst;
            }
            $instances = \array_unique($instances);
            \sort($instances);
            $services[$name] = [
                'returnType' => $method->getReturnType()?->getName(), // @phpstan-ignore-line
                'instances' => $instances,
            ];
        }
        \ksort($services);
        $countServices = \count($services);
        $s = 0; ?>
        <p>There are <?= $countServices ?> services available.</p>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Service</th>
                <th>Config Instances</th>
                <th>Return Type</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($services as $name => $data): ?>
                <?php $count = \count($data['instances']) ?>
                <tr>
                    <td rowspan="<?= $count ?>"><?= ++$s ?></td>
                    <td rowspan="<?= $count ?>"><?= $name ?></td>
                    <td><?= $data['instances'][0] ?></td>
                    <td rowspan="<?= $count ?>"><?= $data['returnType'] ?></td>
                </tr>
                <?php for ($i = 1; $i < $count; $i++): ?>
                    <tr>
                        <td><?= $data['instances'][$i] ?></td>
                    </tr>
                <?php endfor ?>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }
}
