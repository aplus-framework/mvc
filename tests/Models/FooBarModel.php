<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Mvc\Models;

use Aplus\Mvc\Model;

class FooBarModel extends Model
{
    public function getTable() : string
    {
        return parent::getTable();
    }
}
