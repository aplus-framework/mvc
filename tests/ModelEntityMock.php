<?php declare(strict_types=1);
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

class ModelEntityMock extends Entity
{
    public int $id;
    public string $data;
    public Date $createdAt {
        set(string | Date $createdAt) {
            if (\is_string($createdAt)) {
                $createdAt = new Date($createdAt);
            }
            $this->createdAt = $createdAt;
        }
    }
    public Date $updatedAt {
        set(string | Date $updatedAt) {
            if (\is_string($updatedAt)) {
                $updatedAt = new Date($updatedAt);
            }
            $this->updatedAt = $updatedAt;
        }
    }

    #[Override]
    public function toModel() : array
    {
        $data = [
            'data' => $this->data,
        ];
        if (isset($this->id)) {
            $data['id'] = $this->id;
        }
        return $data;
    }
}
