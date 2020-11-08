<?php

namespace PhproTest\DoctrineHydrationModule\Tests\Service;

use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\Filter\FilterInterface;
use Laminas\Hydrator\NamingStrategy\NamingStrategyInterface;
use Laminas\Hydrator\Strategy\StrategyInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
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
    protected function stubObjectManager(string $objectManagerClass)
    {
        $objectManager = $this->getMockBuilder($objectManagerClass)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceManager->setService('doctrine.default.object-manager', $objectManager);

        return $objectManager;
    }

    /**
     * @return \Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator
     */
    protected function createOrmHydrator()
    {
        $this->stubObjectManager('Doctrine\ORM\EntityManager');

        $factory = new DoctrineHydratorFactory();
        $hydrator = $factory($this->serviceManager, 'custom-hydrator');

        return $hydrator;
    }

    /**
     * @return \Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator
     */
    protected function createOdmHydrator()
    {
        $this->stubObjectManager('Doctrine\ODM\MongoDb\DocumentManager');

        $factory = new DoctrineHydratorFactory();
        $hydrator = $factory($this->serviceManager, 'custom-hydrator');

        return $hydrator;
    }

    /**
     * @test
     */
    public function itShouldBeInitializable()
    {
        $factory = new DoctrineHydratorFactory();
        $this->assertInstanceOf(DoctrineHydratorFactory::class, $factory);
    }

    /**
     * @test
     */
    public function itShouldBeAnAbstractFactory()
    {
        $factory = new DoctrineHydratorFactory();
        $this->assertInstanceOf(AbstractFactoryInterface::class, $factory);
    }

    /**
     * @test
     */
    public function itShouldKnowWhichServicesItCanCreate()
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
    public function itShouldCreateACustomORMHydrator()
    {
        $hydrator = $this->createOrmHydrator();

        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator', $hydrator);
        $this->assertInstanceOf('Doctrine\Laminas\Hydrator\DoctrineObject', $hydrator->getExtractService());
        $this->assertInstanceOf('Doctrine\Laminas\Hydrator\DoctrineObject', $hydrator->getHydrateService());
    }

    /**
     * @test
     */
    public function itShouldCreateACustomODMHydrator()
    {
        $hydrator = $this->createOdmHydrator();

        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator', $hydrator);
        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\DoctrineObject', $hydrator->getExtractService());
        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\DoctrineObject', $hydrator->getHydrateService());
    }

    /**
     * @test
     */
    public function itShouldCreateACustomODMHydratorWhichUsesTheAutoGeneratedHydrators()
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
    public function itShouldBePossibleToConfigureACustomHydrator()
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
    public function it_should_be_possible_to_configure_a_custom_hydrator_as_factory()
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
    public function it_should_be_possible_to_configure_hydration_stategies()
    {
        $hydrator = $this->createOrmHydrator();
        $realHydrator = $hydrator->getExtractService();

        $this->assertTrue($realHydrator->hasStrategy('fieldname'));
        $this->assertInstanceOf(StrategyInterface::class, $realHydrator->getStrategy('fieldname'));
    }

    /**
     * @test
     */
    public function it_should_be_possible_to_configure_a_naming_stategy()
    {
        $hydrator = $this->createOrmHydrator();
        $realHydrator = $hydrator->getExtractService();

        $this->assertTrue($realHydrator->hasNamingStrategy());
        $this->assertInstanceOf(NamingStrategyInterface::class, $realHydrator->getNamingStrategy());
    }

    /**
     * @test
     */
    public function it_should_be_possible_to_configure_hydration_filters()
    {
        $hydrator = $this->createOrmHydrator();
        $realHydrator = $hydrator->getExtractService();

        $this->assertTrue($realHydrator->hasFilter('custom.filter.name'));
    }
}
