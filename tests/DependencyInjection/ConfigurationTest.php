<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\DependencyInjection\Configuration;
use Sonata\AdminBundle\Tests\Fixtures\Controller\FooAdminController;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testOptions(): void
    {
        $config = $this->process([]);

        self::assertTrue($config['options']['html5_validate']);
        self::assertNull($config['options']['pager_links']);
        self::assertTrue($config['options']['confirm_exit']);
        self::assertFalse($config['options']['js_debug']);
        self::assertTrue($config['options']['use_icheck']);
        self::assertSame('bundles/sonataadmin/images/default_mosaic_image.png', $config['options']['mosaic_background']);
        self::assertSame('default', $config['options']['default_group']);
        self::assertSame('SonataAdminBundle', $config['options']['default_label_catalogue']);
        self::assertSame('fas fa-folder', $config['options']['default_icon']);
    }

    public function testBreadcrumbsChildRouteDefaultsToEdit(): void
    {
        $config = $this->process([]);

        self::assertSame('edit', $config['breadcrumbs']['child_admin_route']);
    }

    public function testOptionsWithInvalidFormat(): void
    {
        $this->expectException(InvalidTypeException::class);

        $this->process([[
            'options' => [
                'html5_validate' => '1',
            ],
        ]]);
    }

    public function testDefaultAdminServicesDefault(): void
    {
        $config = $this->process([[
            'default_admin_services' => [],
        ]]);

        self::assertSame([
            'model_manager' => null,
            'data_source' => null,
            'field_description_factory' => null,
            'form_contractor' => null,
            'show_builder' => null,
            'list_builder' => null,
            'datagrid_builder' => null,
            'translator' => null,
            'configuration_pool' => null,
            'route_generator' => null,
            'security_handler' => null,
            'menu_factory' => null,
            'route_builder' => null,
            'label_translator_strategy' => null,
            'pager_type' => null,
        ], $config['default_admin_services']);
    }

    public function testDashboardWithoutRoles(): void
    {
        $config = $this->process([]);

        self::assertEmpty($config['dashboard']['blocks'][0]['roles']);
    }

    public function testDashboardWithRoles(): void
    {
        $config = $this->process([[
            'dashboard' => [
                'blocks' => [[
                    'roles' => ['ROLE_ADMIN'],
                    'type' => 'my.type',
                ]],
            ],
        ]]);

        self::assertSame($config['dashboard']['blocks'][0]['roles'], ['ROLE_ADMIN']);
    }

    public function testDashboardGroups(): void
    {
        $config = $this->process([[
            'dashboard' => [
                'groups' => [
                    'bar' => [
                        'label' => 'foo',
                        'icon' => '<i class="fas fa-edit"></i>',
                        'items' => [
                            'item1',
                            'item2',
                            [
                                'label' => 'fooLabel',
                                'route' => 'fooRoute',
                                'route_params' => ['bar' => 'foo'],
                                'route_absolute' => true,
                            ],
                            [
                                'label' => 'barLabel',
                                'route' => 'barRoute',
                            ],
                        ],
                    ],
                ],
            ],
        ]]);

        self::assertCount(4, $config['dashboard']['groups']['bar']['items']);
        self::assertSame(
            $config['dashboard']['groups']['bar']['items'][0],
            [
                'admin' => 'item1',
                'roles' => [],
                'route_params' => [],
                'route_absolute' => false,
            ]
        );
        self::assertSame(
            $config['dashboard']['groups']['bar']['items'][1],
            [
                'admin' => 'item2',
                'roles' => [],
                'route_params' => [],
                'route_absolute' => false,
            ]
        );
        self::assertSame(
            $config['dashboard']['groups']['bar']['items'][2],
            [
                'label' => 'fooLabel',
                'route' => 'fooRoute',
                'route_params' => ['bar' => 'foo'],
                'route_absolute' => true,
                'roles' => [],
            ]
        );
        self::assertSame(
            $config['dashboard']['groups']['bar']['items'][3],
            [
                'label' => 'barLabel',
                'route' => 'barRoute',
                'roles' => [],
                'route_params' => [],
                'route_absolute' => false,
            ]
        );
    }

    public function testDashboardGroupsWithNoRoute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected parameter "route" for array items');

        $this->process([[
            'dashboard' => [
                'groups' => [
                    'bar' => [
                        'label' => 'foo',
                        'icon' => '<i class="fas fa-edit"></i>',
                        'items' => [
                            ['label' => 'noRoute'],
                        ],
                    ],
                ],
            ],
        ]]);
    }

    public function testDashboardGroupsWithNoLabel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected parameter "label" for array items');

        $this->process([[
            'dashboard' => [
                'groups' => [
                    'bar' => [
                        'label' => 'foo',
                        'icon' => '<i class="fas fa-edit"></i>',
                        'items' => [
                            ['route' => 'noLabel'],
                        ],
                    ],
                ],
            ],
        ]]);
    }

    public function testSecurityConfigurationDefaults(): void
    {
        $config = $this->process([[]]);

        self::assertSame('ROLE_SONATA_ADMIN', $config['security']['role_admin']);
        self::assertSame('ROLE_SUPER_ADMIN', $config['security']['role_super_admin']);
    }

    public function testExtraAssetsDefaults(): void
    {
        $config = $this->process([[]]);

        self::assertSame([], $config['assets']['extra_stylesheets']);
        self::assertSame([], $config['assets']['extra_javascripts']);
    }

    public function testRemoveAssetsDefaults(): void
    {
        $config = $this->process([[]]);

        self::assertSame([], $config['assets']['remove_stylesheets']);
        self::assertSame([], $config['assets']['remove_javascripts']);
    }

    public function testDefaultControllerIsCRUDController(): void
    {
        $config = $this->process([]);

        self::assertSame('sonata.admin.controller.crud', $config['default_controller']);
    }

    public function testSettingDefaultController(): void
    {
        $config = $this->process([[
            'default_controller' => FooAdminController::class,
        ]]);

        self::assertSame(FooAdminController::class, $config['default_controller']);
    }

    /**
     * Processes an array of configurations and returns a compiled version.
     *
     * @param array<array<string, mixed>> $configs An array of raw configurations
     *
     * @return array<string, mixed> A normalized array
     */
    protected function process($configs): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }
}
