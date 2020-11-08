<?php

declare(strict_types=1);

namespace PhproTest\DoctrineHydrationModule\Tests\Hydrator\ODM\MongoDB\Strategy;

use Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy\DefaultRelation as StrategyDefaultRelation;
use PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationEmbedMany;
use PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationUser;
use Laminas\Hydrator\Strategy\StrategyInterface;

/**
 * Class EmbeddedFieldTest.
 */
class DefaultRelationTest extends AbstractMongoStrategyTest
{
    /**
     * @return StrategyInterface
     */
    protected function createStrategy(): StrategyInterface
    {
        return new StrategyDefaultRelation();
    }

    /**
     * @test
     */
    public function itShouldExtractEmbeddedCollections(): void
    {
        $user = new HydrationUser();
        $user->setId(1);
        $user->setName('username');

        $embedded = new HydrationEmbedMany();
        $embedded->setId(1);
        $embedded->setName('name');
        $user->addEmbedMany([$embedded]);

        $strategy = $this->getStrategy($this->dm, $user, 'embedMany');
        $result = $strategy->extract($user->getEmbedMany());
        $this->assertEquals('name', $result[0]->getName());
    }

    /**
     * @test
     */
    public function itShouldHydrateEmbeddedCollections(): void
    {
        $user = new HydrationUser();
        $user->setId(1);
        $user->setName('username');

        $embedded = new HydrationEmbedMany();
        $embedded->setId(1);
        $embedded->setName('name');

        $data = [
            $embedded
        ];

        $strategy = $this->getStrategy($this->dm, $user, 'embedMany');
        $strategy->hydrate($data);
        $embedMany = $user->getEmbedMany();
        $this->assertEquals('name', $embedMany[0]->getName());
    }
}
