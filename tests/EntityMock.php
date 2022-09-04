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

use Framework\Date\Date;
use Framework\HTTP\URL;
use Framework\MVC\Entity;
use stdClass;

/**
 * Class EntityMock.
 *
 * @property array $array;
 * @property bool $bool;
 * @property float $float;
 * @property int $int;
 * @property string $string;
 * @property stdClass $stdClass;
 * @property Date $date;
 * @property URL $url;
 * @property mixed $mixed;
 */
class EntityMock extends Entity
{
    public array $_jsonVars = [];
    protected array $array; // @phpstan-ignore-line
    protected bool $bool;
    protected float $float;
    protected int $int;
    protected string $string;
    protected stdClass $stdClass;
    protected Date $date;
    protected URL $url;
    protected mixed $mixed;
    protected $id; // @phpstan-ignore-line
    protected $data; // @phpstan-ignore-line
    protected $createdAt; // @phpstan-ignore-line
    protected $updatedAt; // @phpstan-ignore-line

    public function setId(mixed $id) : static
    {
        $this->id = $id + 1000;
        return $this;
    }

    public function getId() : mixed
    {
        return $this->id;
    }
}
