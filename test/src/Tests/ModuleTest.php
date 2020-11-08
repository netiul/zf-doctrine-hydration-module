<?php

declare(strict_types=1);

namespace PhproTest\DoctrineHydrationModule\Tests;

use Phpro\DoctrineHydrationModule\Module;
use PHPUnit\Framework\TestCase;

/**
 * Class ModuleTest.
 */
class ModuleTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldBeInitializable(): void
    {
        $module = new Module();
        $this->assertInstanceOf('Phpro\DoctrineHydrationModule\Module', $module);
    }

    /**
     * @test
     */
    public function itShouldProvideConfiguration(): void
    {
        $module = new Module();
        $this->assertIsArray($module->getConfig());
    }
}
