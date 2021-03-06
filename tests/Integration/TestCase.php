<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration;

use OxidEsales\EshopCommunity\Tests\Integration\Internal\TestContainerFactory;
use OxidEsales\GraphQL\Base\Framework\GraphQLQueryHandlerInterface;
use OxidEsales\GraphQL\Base\Framework\RequestReader;
use OxidEsales\GraphQL\Base\Framework\RequestReaderInterface;
use OxidEsales\GraphQL\Base\Framework\ResponseWriter;
use OxidEsales\GraphQL\Base\Framework\ResponseWriterInterface;
use OxidEsales\GraphQL\Base\Service\AuthenticationServiceInterface;
use OxidEsales\GraphQL\Base\Service\AuthorizationServiceInterface;
use OxidEsales\TestingLibrary\UnitTestCase as PHPUnitTestCase;
use Psr\Log\LoggerInterface;

abstract class TestCase extends PHPUnitTestCase
{
    protected static $queryResult = null;

    protected static $logResult = null;

    protected static $container = null;

    protected static $query = null;

    protected function setUp(): void
    {
        \OxidEsales\Eshop\Core\Registry::getLang()->resetBaseLanguage();

        if (static::$container !== null) {
            return;
        }
        $containerFactory  = new TestContainerFactory();
        static::$container = $containerFactory->create();

        $responseWriter = new ResponseWriterStub();

        static::$container->set(
            ResponseWriterInterface::class,
            $responseWriter
        );
        static::$container->autowire(
            ResponseWriterInterface::class,
            ResponseWriter::class
        );

        $requestReader = new RequestReaderStub();

        static::$container->set(
            RequestReaderInterface::class,
            $requestReader
        );
        static::$container->autowire(
            RequestReaderInterface::class,
            RequestReader::class
        );

        $logger = new LoggerStub();

        static::$container->set(
            LoggerInterface::class,
            $logger
        );
        static::$container->autowire(
            LoggerInterface::class,
            get_class($logger)
        );

        static::beforeContainerCompile();

        static::$container->compile();
    }

    protected function tearDown(): void
    {
        static::$queryResult = null;
        static::$logResult   = null;
        static::$query       = null;
        static::$container   = null;
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    protected function setAuthToken(string $token): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        $token                         = static::$container->get(RequestReaderInterface::class)
                                   ->getAuthToken();
        static::$container->get(AuthenticationServiceInterface::class)
                          ->setToken($token);
        static::$container->get(AuthorizationServiceInterface::class)
                          ->setToken($token);
    }

    protected function query(string $query, ?array $variables = null, ?string $operationName = null): array
    {
        static::$query = [
            'query'         => $query,
            'variables'     => $variables,
            'operationName' => $operationName,
        ];
        static::$container->get(GraphQLQueryHandlerInterface::class)
                          ->executeGraphQLQuery();

        return static::$queryResult;
    }

    /**
     * @param array{status: int} $result
     */
    protected function assertResponseStatus(int $expectedStatus, array $result): void
    {
        $this->assertEquals(
            $expectedStatus,
            $result['status']
        );
    }

    protected function setGETRequestParameter(string $name, string $value): void
    {
        $_GET[$name] = $value;
    }

    public static function responseCallback($body, $status): void
    {
        static::$queryResult = [
            'status' => $status,
            'body'   => $body,
        ];
    }

    public static function loggerCallback(string $message): void
    {
        static::$logResult .= $message;
    }

    public static function getGraphQLRequestData(): array
    {
        if (static::$query === null) {
            return [];
        }

        return static::$query;
    }

    protected static function beforeContainerCompile(): void
    {
    }
}

// phpcs:disable

class ResponseWriterStub implements ResponseWriterInterface
{
    public function renderJsonResponse(array $result, int $httpStatus): void
    {
        TestCase::responseCallback($result, $httpStatus);
    }
}

class RequestReaderStub extends RequestReader
{
    public function getGraphQLRequestData(string $inputFile = 'php://input'): array
    {
        return TestCase::getGraphQLRequestData();
    }
}

class LoggerStub extends \Psr\Log\AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        TestCase::loggerCallback($message);
    }
}
