<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/** @var \Psr\Container\ContainerInterface $container */
$container = require 'config/container.php';

// Pull the entity manager out of container to avoid duplicate conn config
$entityManager = $container->get(EntityManagerInterface::class);

ConsoleRunner::run(
    new SingleManagerProvider($entityManager),
);
