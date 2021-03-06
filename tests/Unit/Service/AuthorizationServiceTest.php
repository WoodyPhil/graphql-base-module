<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use Lcobucci\JWT\Token;
use OxidEsales\GraphQL\Base\Event\BeforeAuthorizationEvent;
use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;
use OxidEsales\GraphQL\Base\Service\AuthenticationService;
use OxidEsales\GraphQL\Base\Service\AuthorizationService;
# use OxidEsales\TestingLibrary\UnitTestCase as TestCase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AuthorizationServiceTest extends TestCase
{
    public function testIsNotAllowedWithoutPermissionsAndWithoutToken(): void
    {
        $auth = new AuthorizationService(
            [],
            $this->getEventDispatcherMock()
        );
        $this->assertFalse($auth->isAllowed(''));
    }

    public function testIsNotAllowedWithoutPermissionsButWithToken(): void
    {
        $auth = new AuthorizationService(
            [],
            $this->getEventDispatcherMock()
        );
        $auth->setToken(
            $this->getTokenMock()
        );
        $this->assertFalse($auth->isAllowed('foo'));
    }

    public function testIsNotAllowedWithPermissionsButWithoutToken(): void
    {
        $auth = new AuthorizationService(
            $this->getPermissionMocks(),
            $this->getEventDispatcherMock()
        );
        $this->assertFalse($auth->isAllowed('permission'));
    }

    public function testIsAllowedWithPermissionsAndWithToken(): void
    {
        $auth = new AuthorizationService(
            $this->getPermissionMocks(),
            $this->getEventDispatcherMock()
        );
        $auth->setToken(
            $this->getTokenMock()
        );
        $this->assertTrue(
            $auth->isAllowed('permission'),
            'Permission "permission" must be granted to group "group"'
        );
        $this->assertTrue(
            $auth->isAllowed('permission2'),
            'Permission "permission2" must be granted to group "group"'
        );
        $this->assertFalse(
            $auth->isAllowed('permission1'),
            'Permission "permission1" must not be granted to group "group"'
        );
    }

    public function testPositiveOverrideAuthBasedOnEvent(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            BeforeAuthorizationEvent::NAME,
            function (BeforeAuthorizationEvent $event): void {
                $event->setAuthorized(true);
            }
        );
        $auth = new AuthorizationService(
            $this->getPermissionMocks(),
            $eventDispatcher
        );
        $auth->setToken(
            $this->getTokenMock()
        );
        $this->assertTrue(
            $auth->isAllowed('permission'),
            'Permission "permission" must be granted to group "group"'
        );
        $this->assertTrue(
            $auth->isAllowed('permission2'),
            'Permission "permission2" must be granted to group "group"'
        );
        $this->assertTrue(
            $auth->isAllowed('permission1'),
            'Permission "permission1" must be granted to group "group"'
        );
    }

    public function testNegativeOverrideAuthBasedOnEvent(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            BeforeAuthorizationEvent::NAME,
            function (BeforeAuthorizationEvent $event): void {
                $event->setAuthorized(false);
            }
        );
        $auth = new AuthorizationService(
            $this->getPermissionMocks(),
            $eventDispatcher
        );
        $auth->setToken(
            $this->getTokenMock()
        );
        $this->assertFalse(
            $auth->isAllowed('permission'),
            'Permission "permission" must not be granted to group "group"'
        );
        $this->assertFalse(
            $auth->isAllowed('permission2'),
            'Permission "permission2" must not be granted to group "group"'
        );
        $this->assertFalse(
            $auth->isAllowed('permission1'),
            'Permission "permission1" must not be granted to group "group"'
        );
    }

    private function getTokenMock(): Token
    {
        $token = $this->getMockBuilder(Token::class)->getMock();
        $token->method('getClaim')
            ->will($this->returnCallback(
                function ($claim) {
                    if ($claim == AuthenticationService::CLAIM_GROUP) {
                        return 'group';
                    }

                    if ($claim == AuthenticationService::CLAIM_USERNAME) {
                        return 'testuser';
                    }
                }
            ));

        return $token;
    }

    private function getPermissionMocks(): iterable
    {
        $a = $this->getMockBuilder(PermissionProviderInterface::class)->getMock();
        $a->method('getPermissions')
          ->willReturn([
              'group'  => ['permission'],
              'group1' => ['permission1'],
          ]);
        $b = $this->getMockBuilder(PermissionProviderInterface::class)->getMock();
        $b->method('getPermissions')
          ->willReturn([
              'group'     => ['permission2'],
              'group2'    => ['permission2'],
              'developer' => ['all'],
          ]);

        return [
            $a,
            $b,
        ];
    }

    private function getEventDispatcherMock(): EventDispatcherInterface
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
    }
}
