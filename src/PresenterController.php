<?php declare(strict_types=1);
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

abstract class PresenterController extends Controller
{
	abstract protected function index();

	abstract protected function new();

	abstract protected function create();

	abstract protected function show(int $id);

	abstract protected function edit(int $id);

	abstract protected function update(int $id);

	abstract protected function remove(int $id);

	abstract protected function delete(int $id);
}
