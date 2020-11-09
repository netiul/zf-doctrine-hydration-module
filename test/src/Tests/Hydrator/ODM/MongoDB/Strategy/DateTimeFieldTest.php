<?php

declare(strict_types=1);

namespace PhproTest\DoctrineHydrationModule\Tests\Hydrator\ODM\MongoDB\Strategy;

use Laminas\Hydrator\Strategy\StrategyInterface;
use MongoDB\BSON\UTCDateTime;
use Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy\DateTimeField;
use PHPUnit\Framework\TestCase;

/**
 * Class DateTimeFieldTest.
 */
class DateTimeFieldTest extends TestCase
{
    /**
     * @param bool $isTimestamp
     *
     * @return DateTimeField
     */
    protected function createStrategy($isTimestamp = false)
    {
        return new DateTimeField($isTimestamp);
    }

    /**
     * @test
     */
    public function itShouldBeInitializable(): void
    {
        $strategy = $this->createStrategy();
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        $this->assertInstanceOf(DateTimeField::class, $strategy);
    }

    /**
     * @test
     */
    public function itShouldBeAStrategyInterface(): void
    {
        $strategy = $this->createStrategy();
        $this->assertInstanceOf(StrategyInterface::class, $strategy);
    }

    /**
     * @test
     */
    public function itShouldExtractDatetime(): void
    {
        $strategy = $this->createStrategy();
        $date = new \DateTime('1 january 2014');

        $result = $strategy->extract($date);
        $this->assertEquals($date->getTimestamp(), $result);
    }

    /**
     * @test
     */
    public function itShouldHydrateDatetime(): void
    {
        $date = new \DateTime('1 january 2014');
        $dateMongo = new UTCDateTime($date->getTimestamp());
        $dateInt = $date->getTimestamp();
        $dateString = $date->format('Y-m-d');

        $strategy = $this->createStrategy();
        $this->assertEquals($date->getTimestamp(), $strategy->hydrate($date, null)->getTimestamp());
        $this->assertEquals($date->getTimestamp(), $strategy->hydrate($dateMongo, null)->getTimestamp());
        $this->assertEquals($date->getTimestamp(), $strategy->hydrate($dateInt, null)->getTimestamp());
        $this->assertEquals($date->getTimestamp(), $strategy->hydrate($dateString, null)->getTimestamp());
    }

    /**
     * @test
     */
    public function itShouldHydrateTimestamps(): void
    {
        $date = new \DateTime('1 january 2014');
        $dateMongo = new UTCDateTime($date->getTimestamp());
        $dateInt = $date->getTimestamp();
        $dateString = $date->format('Y-m-d');

        $strategy = $this->createStrategy(true);
        $this->assertEquals($date->getTimestamp(), $strategy->hydrate($date, null));
        $this->assertEquals($date->getTimestamp(), $strategy->hydrate($dateMongo, null));
        $this->assertEquals($date->getTimestamp(), $strategy->hydrate($dateInt, null));
        $this->assertEquals($date->getTimestamp(), $strategy->hydrate($dateString, null));
    }
}
