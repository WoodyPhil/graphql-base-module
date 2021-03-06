<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType;

use Doctrine\DBAL\Query\QueryBuilder;
use TheCodingMachine\GraphQLite\Annotations\Factory;

use function strtoupper;

class BoolFilter implements FilterInterface
{
    /** @var bool */
    private $equals;

    public function __construct(
        bool $equals = true
    ) {
        $this->equals = $equals;
    }

    public function equals(): bool
    {
        return $this->equals;
    }

    public function addToQuery(QueryBuilder $builder, string $field): void
    {
        $builder->andWhere(strtoupper($field) . ' = :' . $field)
                ->setParameter(':' . $field, $this->equals ? '1' : '0');
        // if equals is set, then no other conditions may apply
    }

    /**
     * @Factory(name="BoolFilterInput")
     */
    public static function fromUserInput(
        bool $equals
    ): self {
        return new self(
            $equals
        );
    }
}
