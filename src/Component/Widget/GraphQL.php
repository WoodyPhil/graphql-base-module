<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Component\Widget;

use OxidEsales\Eshop\Application\Component\Widget\WidgetController;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\GraphQL\Base\Framework\GraphQLQueryHandlerInterface;

/**
 * Class GraphQL
 *
 * Implements the GraphQL widget for the OXID eShop to make all
 * of this callable via a SEO Url or via widget.php?cl=graphql
 *
 * @package OxidEsales\GraphQL\Base\Component\Widget
 */
class GraphQL extends WidgetController
{
    /**
     * Init function
     */
    public function init(): void
    {
        ContainerFactory::getInstance()
            ->getContainer()
            ->get(GraphQLQueryHandlerInterface::class)
            ->executeGraphQLQuery();
    }
}
