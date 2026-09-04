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
use Framework\MVC\Entity;
use Override;

/**
 * Class EntityMock.
 */
class EntityMock extends Entity
{
    public int $id {
        set(string | int $id) {
            $this->id = (int) $id;
        }
    }
    public string $name {
        set(string $name) {
            $name = \preg_replace('/\s+/', ' ', $name);
            $name = \trim($name);
            $this->name = $name;
        }
    }
    public Date $birthday {
        set(Date | string $birthday) {
            if(\is_string($birthday)) {
                $birthday = new Date($birthday);
            }
            $this->birthday = $birthday;
        }
    }
    /**
     * @var array<string,mixed>
     */
    public array $configs {
        set(array | string $configs) {
            if(\is_string($configs)) {
                $configs = \json_decode($configs, true);
            }
            $this->configs = $configs;
        }
    }

    public private(set) int $currentTime;

    #[Override]
    protected function init() : void
    {
        parent::init();
        $this->currentTime = \time();
    }

    #[Override]
    public function toModel() : array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'birthday' => $this->birthday->format('Y-m-d'),
            'configs' => \json_encode($this->configs),
        ];
    }

    #[Override]
    public function toJson() : array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'birthday' => $this->birthday,
            'configs' => $this->configs,
        ];
    }
}
