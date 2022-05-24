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
use Framework\Validation\Validation;

class ModelMock extends Model
{
    public string $returnType = 'object';
    public array $allowedFields = ['data'];
    public bool $autoTimestamps = true;
    public array $validationRules = [];
    public array $validationMessages = [];
    public bool $protectPrimaryKey = true;
    public string $pagerView;
    public string $pagerUrl;

    /**
     * @param int $page
     * @param int $per_page
     *
     * @return array<int,int|null>
     */
    public function makePageLimitAndOffset(int $page, int $per_page = 10) : array
    {
        return parent::makePageLimitAndOffset($page, $per_page);
    }

    public function getValidation() : Validation
    {
        return parent::getValidation();
    }
}
