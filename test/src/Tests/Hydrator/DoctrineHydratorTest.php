<?php

//declare(strict_types=1);

namespace PhproTest\DoctrineHydrationModule\Tests\Hydrator;

use Laminas\Hydrator\HydratorInterface;
use Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Class DoctrineHydratorTest.
 */
class DoctrineHydratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param HydratorInterface|\Doctrine\ODM\MongoDB\Hydrator\HydratorInterface|null $hydrateService
     * @param HydratorInterface|null $extractService
     *
     * @return DoctrineHydrator
     */
    protected function createHydrator($hydrateService = null, ?HydratorInterface $extractService = null): DoctrineHydrator
    {
        $hydrateService = $hydrateService ? $hydrateService : $this->getMockBuilder(HydratorInterface::class)->getMock();
        $extractService = $extractService ? $extractService : $this->getMockBuilder(HydratorInterface::class)->getMock();

        return new DoctrineHydrator($extractService, $hydrateService);
    }

    /**
     * @test
     */
    public function itShouldBeInitializable(): void
    {
        $hydrator = $this->createHydrator();
        /** @psalm-suppress RedundantCondition  */
        $this->assertInstanceOf(DoctrineHydrator::class, $hydrator);
    }

    /**
     * @test
     */
    public function itShouldHaveAHydratorService(): void
    {
        $hydrator = $this->createHydrator();
        $this->assertInstanceOf(HydratorInterface::class, $hydrator->getHydrateService());
    }

    /**
     * @test
     */
    public function itShouldHaveAnExtractorService(): void
    {
        $hydrator = $this->createHydrator();
        $this->assertInstanceOf(HydratorInterface::class, $hydrator->getExtractService());
    }

    /**
     * @test
     */
    public function itShouldExtractAnObject(): void
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
    public function itShouldHydrateAnObject(): void
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
    public function itShouldUseAGeneratedDoctrineHydratorWhileHydratingAnObject(): void
    {
        $object = new \stdClass();
        $data = ['field' => 'value'];

        $hydrateService = $this->prophesize(\Doctrine\ODM\MongoDB\Hydrator\HydratorInterface::class);
        $hydrateService->hydrate(Argument::is($object), Argument::is($data))->willReturn($data);

        $hydrator = $this->createHydrator($hydrateService->reveal(), null);
        $result = $hydrator->hydrate($data, $object);

        $this->assertEquals($object, $result);
    }
}
