<?php

declare(strict_types=1);

namespace PhproTest\DoctrineHydrationModule\Tests\Hydrator;

use Laminas\Hydrator\HydratorInterface;
use Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator;
use PHPUnit\Framework\TestCase;

/**
 * Class DoctrineHydratorTest.
 */
class DoctrineHydratorTest extends TestCase
{
    /**
     * @param null $hydrateService
     * @param null $extractService
     *
     * @return DoctrineHydrator
     */
    protected function createHydrator($hydrateService = null, $extractService = null)
    {
        $hydrateService = $hydrateService ? $hydrateService : $this->getMockBuilder('Laminas\Hydrator\HydratorInterface')->getMock();
        $extractService = $extractService ? $extractService : $this->getMockBuilder('Laminas\Hydrator\HydratorInterface')->getMock();

        return new DoctrineHydrator($extractService, $hydrateService);
    }

    /**
     * @test
     */
    public function it_should_be_initializable()
    {
        $hydrator = $this->createHydrator();
        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator', $hydrator);
    }

    /**
     * @test
     */
    public function it_should_have_a_hydrator_service()
    {
        $hydrator = $this->createHydrator();
        $this->assertInstanceOf('Laminas\Hydrator\HydratorInterface', $hydrator->getHydrateService());
    }

    /**
     * @test
     */
    public function it_should_have_an_extractor_service()
    {
        $hydrator = $this->createHydrator();
        $this->assertInstanceOf('Laminas\Hydrator\HydratorInterface', $hydrator->getExtractService());
    }

    /**
     * @test
     */
    public function it_should_extract_an_object()
    {
        $object = new \stdClass();
        $extracted = ['extracted' => true];
        $extractService = $this->getMockBuilder(HydratorInterface::class)->getMock();
        $extractService
            ->expects($this->any())
            ->method('extract')
            ->will($this->returnValue($extracted));

        $hydrator = $this->createHydrator(null, $extractService);
        $result = $hydrator->extract($object);

        $this->assertEquals($extracted, $result);
    }

    /**
     * @test
     */
    public function it_should_hydrate_an_object()
    {
        $object = new \stdClass();
        $data = ['field' => 'value'];

        $hydrateService = $this->getMockBuilder(HydratorInterface::class)->getMock();
        $hydrateService
            ->expects($this->any())
            ->method('hydrate')
            ->with($data, $object)
            ->will($this->returnValue($object));

        $hydrator = $this->createHydrator($hydrateService, null);
        $result = $hydrator->hydrate($data, $object);

        $this->assertEquals($object, $result);
    }

    /**
     * @test
     */
    public function it_should_use_a_generated_doctrine_hydrator_while_hydrating_an_object()
    {
        $object = new \stdClass();
        $data = ['field' => 'value'];

        $hydrateService = $this->getMockBuilder(\Doctrine\ODM\MongoDB\Hydrator\HydratorInterface::class)->getMock();
        $hydrateService
            ->expects($this->any())
            ->method('hydrate')
            ->with($object, $data)
            ->will($this->returnValue($data));

        $hydrator = $this->createHydrator($hydrateService, null);
        $result = $hydrator->hydrate($data, $object);

        $this->assertEquals($object, $result);
    }
}
