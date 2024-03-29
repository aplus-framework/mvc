<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC;

use Framework\MVC\Model;

class ModelCacheMock extends Model
{
    protected string $table = 'ModelMock';
    protected array $allowedFields = ['data'];
    protected bool $autoTimestamps = true;
    protected array $validationRules = [
        'data' => 'minLength:3',
    ];
    protected bool $cacheActive = true;

    public function updateCachedRow(string $column, int | string $value) : void
    {
        parent::updateCachedRow($column, $value);
    }
}
