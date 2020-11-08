<?php

declare(strict_types=1);

namespace PhproTest\DoctrineHydrationModule\Tests\Hydrator\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\DoctrineObject;
use PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationEmbedMany;
use PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationEmbedOne;
use PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationReferenceMany;
use PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationReferenceOne;
use PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationUser;

/**
 * Class DoctrineObjectTest.
 */
class DoctrineObjectTest extends BaseTest
{
    /**
     * @param null $objectManager
     *
     * @return DoctrineObject
     */
    protected function createHydrator($objectManager = null): DoctrineObject
    {
        $objectManager = $objectManager ? $objectManager : $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        return new DoctrineObject($objectManager);
    }

    /**
     * @test
     */
    public function itShouldBeInitializable(): void
    {
        $hydrator = $this->createHydrator();
        $this->assertInstanceOf(DoctrineObject::class, $hydrator);
    }

    /**
     * @test
     */
    public function itShouldBeADoctrineHydrator(): void
    {
        $hydrator = $this->createHydrator();
        $this->assertInstanceOf(\Doctrine\Laminas\Hydrator\DoctrineObject::class, $hydrator);
    }

    /**
     * @test
     */
    public function itShouldExtractADocument(): void
    {
        $creationDate = new \DateTime();
        $birthday = new \DateTime('1 january 2014');

        $user = new HydrationUser();
        $user->setId(1);
        $user->setName('user');
        $user->setCreatedAt($creationDate->getTimestamp());
        $user->setBirthday($birthday);

        $embedOne = new HydrationEmbedOne();
        $embedOne->setId(1);
        $embedOne->setName('name');
        $user->setEmbedOne($embedOne);

        $embedMany = new HydrationEmbedMany();
        $embedMany->setId(1);
        $embedMany->setName('name');
        $user->addEmbedMany([$embedMany]);

        $referenceOne = new HydrationReferenceOne();
        $referenceOne->setId(1);
        $referenceOne->setName('name');
        $user->setReferenceOne($referenceOne);

        $referenceMany = new HydrationEmbedMany();
        $referenceMany->setId(1);
        $referenceMany->setName('name');
        $user->addReferenceMany([$referenceMany]);

        $hydrator = new DoctrineObject($this->dm);
        $result = $hydrator->extract($user);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('user', $result['name']);
        $this->assertEquals($creationDate->getTimestamp(), $result['createdAt']);
        $this->assertEquals($birthday->getTimestamp(), $result['birthday']);
        $this->assertEquals(1, $result['embedOne']['id']);
        $this->assertEquals('name', $result['embedOne']['name']);
        $this->assertEquals(1, $result['embedMany'][0]['id']);
        $this->assertEquals('name', $result['embedMany'][0]['name']);
        $this->assertEquals(1, $result['referenceOne']);
        $this->assertEquals(1, $result['referenceMany'][0]);
    }

    /**
     * @test
     */
    public function itShouldHydrateADocument(): void
    {
        $creationDate = new \DateTime();
        $birthday = new \DateTime('1 january 2014');

        $user = new HydrationUser();
        $data = [
            'id' => 1,
            'name' => 'user',
            'creationDate' => $creationDate->getTimestamp(),
            'birthday' => $birthday->getTimestamp(),
            'referenceOne' => $this->createReferenceOne('name'),
            'referenceMany' => [$this->createReferenceMany('name')],
            'embedOne' => [
                'id' => 1,
                'name' => 'name',
            ],
            'embedMany' => [
                [
                    'id' => 1,
                    'name' => 'name',
                ],
            ],
        ];

        $hydrator = new DoctrineObject($this->dm);
        $hydrator->hydrate($data, $user);

        $this->assertEquals(1, $user->getId());
        $this->assertEquals('user', $user->getName());
        $this->assertEquals($creationDate->getTimestamp(), $user->getCreatedAt());
        $this->assertEquals($birthday->getTimestamp(), $user->getBirthday()->getTimestamp());
        $this->assertInstanceOf('PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationReferenceOne', $user->getReferenceOne());
        $referenceMany = $user->getReferenceMany();
        $this->assertInstanceOf('PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationReferenceMany', $referenceMany[0]);
        $this->assertInstanceOf('PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationEmbedOne', $user->getEmbedOne());
        $embedMany = $user->getEmbedMany();
        $this->assertInstanceOf('PhproTest\DoctrineHydrationModule\Fixtures\ODM\MongoDb\HydrationEmbedMany', $embedMany[0]);
        $this->assertEquals('name', $user->getReferenceOne()->getName());
        $this->assertEquals('name', $referenceMany[0]->getName());
        $this->assertEquals('name', $user->getEmbedOne()->getName());
        $this->assertEquals('name', $embedMany[0]->getName());
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function createReferenceOne($name): string
    {
        $embedded = new HydrationReferenceOne();
        $embedded->setName($name);

        $this->dm->persist($embedded);
        $this->dm->flush();

        return $embedded->getId();
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function createReferenceMany($name): string
    {
        $embedded = new HydrationReferenceMany();
        $embedded->setName($name);

        $this->dm->persist($embedded);
        $this->dm->flush();

        return $embedded->getId();
    }
}
