<?php namespace Framework\MVC;

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
