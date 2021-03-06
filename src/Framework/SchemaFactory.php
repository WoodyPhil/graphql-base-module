<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Framework;

use Mouf\Composer\ClassNameMapper;
use OxidEsales\GraphQL\Base\Service\AuthenticationServiceInterface;
use OxidEsales\GraphQL\Base\Service\AuthorizationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TheCodingMachine\GraphQLite\Schema;
use TheCodingMachine\GraphQLite\SchemaFactory as GraphQLiteSchemaFactory;

/**
 * Class SchemaFactory
 */
class SchemaFactory implements SchemaFactoryInterface
{
    /** @var Schema */
    private $schema;

    /** @var AuthenticationServiceInterface */
    private $authenticationService;

    /** @var AuthorizationServiceInterface */
    private $authorizationService;

    /** @var NamespaceMapperInterface[] */
    private $namespaceMappers;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param NamespaceMapperInterface[] $namespaceMappers
     */
    public function __construct(
        iterable $namespaceMappers,
        AuthenticationServiceInterface $authenticationService,
        AuthorizationServiceInterface $authorizationService,
        ContainerInterface $container
    ) {
        foreach ($namespaceMappers as $namespaceMapper) {
            $this->namespaceMappers[] = $namespaceMapper;
        }
        $this->authenticationService = $authenticationService;
        $this->authorizationService  = $authorizationService;
        $this->container             = $container;
    }

    public function getSchema(): Schema
    {
        if (null !== $this->schema) {
            return $this->schema;
        }

        $factory = new GraphQLiteSchemaFactory(
            new \Symfony\Component\Cache\Simple\NullCache(),
            $this->container
        );

        $classNameMapper = new ClassNameMapper();

        foreach ($this->namespaceMappers as $namespaceMapper) {
            foreach ($namespaceMapper->getControllerNamespaceMapping() as $namespace => $path) {
                $classNameMapper->registerPsr4Namespace(
                    $namespace,
                    $path
                );
                $factory->addControllerNameSpace($namespace);
            }

            foreach ($namespaceMapper->getTypeNamespaceMapping() as $namespace => $path) {
                $classNameMapper->registerPsr4Namespace(
                    $namespace,
                    $path
                );
                $factory->addTypeNameSpace($namespace);
            }
        }

        $factory->setClassNameMapper($classNameMapper);

        $factory->setAuthenticationService($this->authenticationService)
                ->setAuthorizationService($this->authorizationService);

        return $this->schema = $factory->createSchema();
    }
}
