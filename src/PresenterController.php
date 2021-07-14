<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

use Framework\Routing\PresenterInterface;

abstract class PresenterController extends Controller implements PresenterInterface
{
    public function index() : mixed
    {
        return __METHOD__;
    }

    public function new() : mixed
    {
        return __METHOD__;
    }

    public function create() : mixed
    {
        return __METHOD__;
    }

    public function show(string $id) : mixed
    {
        return __METHOD__ . '/' . $id;
    }

    public function edit(string $id) : mixed
    {
        return __METHOD__ . '/' . $id;
    }

    public function update(string $id) : mixed
    {
        return __METHOD__ . '/' . $id;
    }

    public function remove(string $id) : mixed
    {
        return __METHOD__ . '/' . $id;
    }

    public function delete(string $id) : mixed
    {
        return __METHOD__ . '/' . $id;
    }
}
