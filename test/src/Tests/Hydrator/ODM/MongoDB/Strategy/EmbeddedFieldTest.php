<?php

declare(strict_types=1);

namespace PhproTest\DoctrineHydrationModule\Tests\Hydrator\ODM\MongoDB\Strategy;

use Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy\EmbeddedField;
use PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationEmbedOne;
use PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationUser;
use Laminas\Hydrator\Strategy\StrategyInterface;

/**
 * Class EmbeddedFieldTest.
 */
class EmbeddedFieldTest extends AbstractMongoStrategyTest
{
    /**
     * @return StrategyInterface
     */
    protected function createStrategy(): StrategyInterface
    {
        return new EmbeddedField();
    }

    /**
     * @test
     */
    public function itShouldNotBreakWhenEmbedFieldNotSet()
    {
        $user = new HydrationUser();
        $user->setId(1);
        $user->setName('username');

        $embedded = new HydrationEmbedOne();
        $embedded->setId(1);
        $embedded->setName('name');
        $strategy = $this->getStrategy($this->dm, $user, 'embedOne');
        $result = $strategy->extract($user->getEmbedOne());
        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function itShouldExtractEmbeddedFields()
    {
        $user = new HydrationUser();
        $user->setId(1);
        $user->setName('username');

        $embedded = new HydrationEmbedOne();
        $embedded->setId(1);
        $embedded->setName('name');
        $user->setEmbedOne($embedded);

        $strategy = $this->getStrategy($this->dm, $user, 'embedOne');
        $result = $strategy->extract($user->getEmbedOne());
        $this->assertEquals('name', $result['name']);
    }

    /**
     * @test
     */
    public function itShouldHydrateEmbeddedFields()
    {
        $user = new HydrationUser();
        $user->setId(1);
        $user->setName('username');

        $data = [
            'id' => 1,
            'name' => 'name',
        ];

        $strategy = $this->getStrategy($this->dm, $user, 'embedOne');
        $result = $strategy->hydrate($data);
        $this->assertEquals('name', $result->getName());
    }
}
