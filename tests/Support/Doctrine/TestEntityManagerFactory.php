<?php

declare(strict_types=1);

namespace App\Tests\Support\Doctrine;

use App\Domain\Type\EmailType as DomainEmailType;
use App\Domain\Type\JsonbType;
use App\Domain\Type\UuidType;
use App\Infrastructure\Doctrine\Type\AddressType;
use App\Infrastructure\Doctrine\Type\MoneyType;
use App\Infrastructure\Doctrine\Type\OrderNumberType;
use App\Infrastructure\Doctrine\Type\PhoneType;
use App\Infrastructure\Doctrine\Type\ProductSkuType;
use App\Infrastructure\Doctrine\Type\SlugType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

final class TestEntityManagerFactory
{
    /**
     * @param list<string> $extraEntityPaths
     */
    public static function create(array $extraEntityPaths = []): EntityManagerInterface
    {
        self::registerTypes();

        $entityPaths = array_merge([
            __DIR__ . '/../../../src/Domain/Entity',
        ], $extraEntityPaths);

        $config = ORMSetup::createAttributeMetadataConfiguration($entityPaths, true);

        $entityManager = EntityManager::create([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $platform = $entityManager->getConnection()->getDatabasePlatform();

        foreach (array_keys(self::customTypes()) as $name) {
            if (method_exists($platform, 'registerDoctrineTypeMapping')) {
                $platform->registerDoctrineTypeMapping($name, $name);
            }
        }

        return $entityManager;
    }

    /**
     * @return array<string, class-string<Type>>
     */
    private static function customTypes(): array
    {
        return [
            JsonbType::NAME => JsonbType::class,
            UuidType::NAME => UuidType::class,
            DomainEmailType::NAME => DomainEmailType::class,
            MoneyType::NAME => MoneyType::class,
            ProductSkuType::NAME => ProductSkuType::class,
            SlugType::NAME => SlugType::class,
            AddressType::NAME => AddressType::class,
            OrderNumberType::NAME => OrderNumberType::class,
            PhoneType::NAME => PhoneType::class,
        ];
    }

    private static function registerTypes(): void
    {
        foreach (self::customTypes() as $name => $class) {
            if (Type::hasType($name)) {
                Type::overrideType($name, $class);
            } else {
                Type::addType($name, $class);
            }
        }
    }
}
