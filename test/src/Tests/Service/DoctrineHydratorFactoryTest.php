<?php

declare(strict_types=1);

namespace PhproTest\DoctrineHydrationModule\Tests\Service;

use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\Filter\FilterInterface;
use Laminas\Hydrator\NamingStrategy\NamingStrategyInterface;
use Laminas\Hydrator\Strategy\StrategyInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator;
use PhproTest\DoctrineHydrationModule\Hydrator\CustomBuildHydratorFactory;
use Phpro\DoctrineHydrationModule\Service\DoctrineHydratorFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Hydrator\HydratorPluginManager;

class DoctrineHydratorFactoryTest extends TestCase
{
    /**
     * @var array
     */
    protected $serviceConfig;

    /**
     * @var HydratorPluginManager
     */
    protected $hydratorManager;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Setup the service manager.
     */
    protected function setUp(): void
    {
        /** @psalm-suppress UnresolvableInclude Path is set in test run */
        $this->serviceConfig = require TEST_BASE_PATH . '/config/module.config.php';

        $this->serviceManager = new ServiceManager();
        $this->serviceManager->setAllowOverride(true);
        $this->serviceManager->setService('config', $this->serviceConfig);
        $this->serviceManager->setService(
            'custom.strategy',
            $this->getMockBuilder(StrategyInterface::class)->getMock()
        );
        $this->serviceManager->setService(
            'custom.filter',
            $this->getMockBuilder(FilterInterface::class)->getMock()
        );
        $this->serviceManager->setService(
            'custom.naming_strategy',
            $this->getMockBuilder(NamingStrategyInterface::class)->getMock()
        );

        $this->hydratorManager = $this->getMockBuilder(HydratorPluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hydratorManager
            ->expects($this->any())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->serviceManager));
    }

    /**
     * @param string $objectManagerClass
     *
     * @return MockObject
     */
    protected function stubObjectManager(string $objectManagerClass): MockObject
    {
        $objectManager = $this->getMockBuilder($objectManagerClass)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceManager->setService('doctrine.default.object-manager', $objectManager);

        return $objectManager;
    }

    /**
     * @return DoctrineHydrator
     */
    protected function createOrmHydrator(): DoctrineHydrator
    {
        $this->stubObjectManager('Doctrine\ORM\EntityManager');

        $factory = new DoctrineHydratorFactory();
        return $factory($this->serviceManager, 'custom-hydrator');
    }

    /**
     * @return DoctrineHydrator
     */
    protected function createOdmHydrator(): DoctrineHydrator
    {
        $this->stubObjectManager('Doctrine\ODM\MongoDb\DocumentManager');

        $factory = new DoctrineHydratorFactory();
        return $factory($this->serviceManager, 'custom-hydrator');
    }

    /**
     * @test
     */
    public function itShouldBeInitializable(): void
    {
        $factory = new DoctrineHydratorFactory();
        $this->assertInstanceOf(DoctrineHydratorFactory::class, $factory);
    }

    /**
     * @test
     */
    public function itShouldBeAnAbstractFactory(): void
    {
        $factory = new DoctrineHydratorFactory();
        $this->assertInstanceOf(AbstractFactoryInterface::class, $factory);
    }

    /**
     * @test
     */
    public function itShouldKnowWhichServicesItCanCreate(): void
    {
        // $this->stubObjectManager('Doctrine\Common\Persistence\ObjectManager');
        $factory = new DoctrineHydratorFactory();

        $result = $factory->canCreate($this->serviceManager, 'custom-hydrator');
        $this->assertTrue($result);

        $result = $factory->canCreate($this->serviceManager, 'invalid-hydrator');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function itShouldCreateACustomORMHydrator(): void
    {
        $hydrator = $this->createOrmHydrator();

        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator', $hydrator);
        $this->assertInstanceOf('Doctrine\Laminas\Hydrator\DoctrineObject', $hydrator->getExtractService());
        $this->assertInstanceOf('Doctrine\Laminas\Hydrator\DoctrineObject', $hydrator->getHydrateService());
    }

    /**
     * @test
     */
    public function itShouldCreateACustomODMHydrator(): void
    {
        $hydrator = $this->createOdmHydrator();

        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator', $hydrator);
        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\DoctrineObject', $hydrator->getExtractService());
        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\DoctrineObject', $hydrator->getHydrateService());
    }

    /**
     * @test
     */
    public function itShouldCreateACustomODMHydratorWhichUsesTheAutoGeneratedHydrators(): void
    {
        $this->serviceConfig['doctrine-hydrator']['custom-hydrator']['use_generated_hydrator'] = true;
        $this->serviceManager->setService('config', $this->serviceConfig);
        $objectManager = $this->stubObjectManager('Doctrine\ODM\MongoDb\DocumentManager');

        $hydratorFactory = $this->getMockBuilder('Doctrine\ODM\MongoDB\Hydrator\HydratorFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $generatedHydrator = $this->getMockBuilder('Doctrine\ODM\MongoDB\Hydrator\HydratorInterface')->getMock();

        $objectManager
            ->expects($this->any())
            ->method('getHydratorFactory')
            ->will($this->returnValue($hydratorFactory));

        $hydratorFactory
            ->expects($this->any())
            ->method('getHydratorFor')
            ->with('App\Entity\EntityClass')
            ->will($this->returnValue($generatedHydrator));

        $factory = new DoctrineHydratorFactory();
        $hydrator = $factory($this->serviceManager, 'custom-hydrator');

        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator', $hydrator);
        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\DoctrineObject', $hydrator->getExtractService());
        $this->assertEquals($generatedHydrator, $hydrator->getHydrateService());
    }

    /**
     * @test
     */
    public function itShouldBePossibleToConfigureACustomHydrator(): void
    {
        $this->serviceConfig['doctrine-hydrator']['custom-hydrator']['hydrator'] = 'custom.hydrator';
        $this->serviceManager->setService('config', $this->serviceConfig);

        $this->serviceManager->setService(
            'custom.hydrator',
            $this->getMockBuilder(ArraySerializableHydrator::class)->getMock()
        );

        $hydrator = $this->createOrmHydrator();

        $this->assertInstanceOf(ArraySerializableHydrator::class, $hydrator->getHydrateService());
        $this->assertInstanceOf(ArraySerializableHydrator::class, $hydrator->getExtractService());
    }

    /**
     * @test
     */
    public function itShouldBePossibleToConfigureACustomHydratorAsFactory(): void
    {
        $this->serviceConfig['doctrine-hydrator']['custom-hydrator']['hydrator'] = 'custom.build.hydrator';
        $this->serviceManager->setService('config', $this->serviceConfig);

        $this->serviceManager->setFactory(
            'custom.build.hydrator',
            new CustomBuildHydratorFactory()
        );

        $hydrator = $this->createOrmHydrator();

        $this->assertInstanceOf(ArraySerializableHydrator::class, $hydrator->getHydrateService());
        $this->assertInstanceOf(ArraySerializableHydrator::class, $hydrator->getExtractService());
    }

    /**
     * @test
     */
    public function itShouldBePossibleToConfigureHydrationStategies(): void
    {
        $hydrator = $this->createOrmHydrator();
        $realHydrator = $hydrator->getExtractService();

        $this->assertTrue($realHydrator->hasStrategy('fieldname'));
        $this->assertInstanceOf(StrategyInterface::class, $realHydrator->getStrategy('fieldname'));
    }

    /**
     * @test
     */
    public function itShouldBePossibleToConfigureANamingStategy(): void
    {
        $hydrator = $this->createOrmHydrator();
        $realHydrator = $hydrator->getExtractService();

        $this->assertTrue($realHydrator->hasNamingStrategy());
        $this->assertInstanceOf(NamingStrategyInterface::class, $realHydrator->getNamingStrategy());
    }

    /**
     * @test
     */
    public function itShouldBePossibleToConfigureHydrationFilters(): void
    {
        $hydrator = $this->createOrmHydrator();
        $realHydrator = $hydrator->getExtractService();

        $this->assertTrue($realHydrator->hasFilter('custom.filter.name'));
    }
}
