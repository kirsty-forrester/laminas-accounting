<?php

declare(strict_types=1);

namespace Accounting\Persistence;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Psr\Container\ContainerInterface;

class EntityManagerFactory
{
    public function __invoke(ContainerInterface $container): EntityManagerInterface
    {
        $config = $container->get('config')['doctrine'];

        // 1. Build ORM configuration told to read mappings from XML files.
        $ormConfig = ORMSetup::createXMLMetadataConfiguration(
            paths: $config['mapping_paths'],
            isDevMode: $config['dev_mode'],
            proxyDir: $config['proxy_dir'],
        );

        // 2. Open a DBAL connection from our params, bound to that config.
        $connection = DriverManager::getConnection($config['connection'], $ormConfig);

        // 3. The EntityManager ties the connection and the mapping together.
        //    Note: ORM 3 removed EntityManager::create(); we construct directly.
        return new EntityManager($connection, $ormConfig);
    }
}
