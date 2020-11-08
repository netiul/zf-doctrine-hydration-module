<?php

declare(strict_types=1);

namespace PhproTest\DoctrineHydrationModule\Tests\Hydrator\ODM\MongoDB\Strategy;

use Doctrine\Laminas\Hydrator\Strategy\AbstractCollectionStrategy;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Laminas\Hydrator\Strategy\StrategyInterface;
use Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy\AbstractMongoStrategy;

/**
 * Class AbstractMongoStrategyTest.
 */
abstract class AbstractMongoStrategyTest extends BaseTest
{
    /**
     * @return StrategyInterface
     */
    abstract protected function createStrategy(): StrategyInterface;

    /**
     * @param DocumentManager $objectManager
     * @param object $object
     * @param string $fieldName
     *
     * @return StrategyInterface
     */
    protected function getStrategy(DocumentManager $objectManager, object $object, string $fieldName): StrategyInterface
    {
        $objectClass = get_class($object);
        $metadata = $objectManager->getClassMetadata($objectClass);

        $strategy = $this->createStrategy();
        $strategy->setObject($object);
        $strategy->setObjectManager($objectManager);
        $strategy->setCollectionName($fieldName);
        $strategy->setClassMetadata($metadata);

        return $strategy;
    }

    /**
     * @test
     */
    public function itShouldBeAMongodbStrategy(): void
    {
        $strategy = $this->createStrategy();
        $this->assertInstanceOf(AbstractMongoStrategy::class, $strategy);
    }

    /**
     * @test
     */
    public function itShouldbeACollectionStrategy(): void
    {
        $strategy = $this->createStrategy();
        $this->assertInstanceOf(AbstractCollectionStrategy::class, $strategy);
    }

    /**
     * @test
     */
    public function itShouldknowAnObjectManager()
    {
        $strategy = $this->createStrategy();
        $this->assertInstanceOf(ObjectManagerAwareInterface::class, $strategy);
    }

    /**
     * @test
     */
    public function itShouldHaveAnObjectManager()
    {
        $objectManager = $this->dm;
        $strategy = $this->createStrategy();

        $strategy->setObjectManager($objectManager);
        $this->assertEquals($objectManager, $strategy->getObjectManager());
    }
}
