<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerCacheWarmer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\Mapping\Loader\XmlFileLoader;
use Symfony\Component\Serializer\Mapping\Loader\YamlFileLoader;

class SerializationCacheWarmerTest extends TestCase
{
    public function testWarmUp()
    {
        $loader = new LoaderChain(array(
            new XmlFileLoader(__DIR__.'/../Fixtures/Serialization/Resources/person.xml'),
            new YamlFileLoader(__DIR__.'/../Fixtures/Serialization/Resources/author.yml'),
        ));

        $file = sys_get_temp_dir().'/cache-serializer.php';
        @unlink($file);

        $fallbackPool = new ArrayAdapter();

        $warmer = new SerializerCacheWarmer($loader, $file, $fallbackPool);
        $warmer->warmUp(dirname($file));

        $this->assertFileExists($file);

        $values = require $file;

        $this->assertInternalType('array', $values);
        $this->assertCount(3, $values); // 2 classes + Doctrine namespace
        $this->assertArrayHasKey('%5BSymfony%5CBundle%5CFrameworkBundle%5CTests%5CFixtures%5CSerialization%5CPerson%5D%5B1%5D', $values);
        $this->assertArrayHasKey('%5BSymfony%5CBundle%5CFrameworkBundle%5CTests%5CFixtures%5CSerialization%5CAuthor%5D%5B1%5D', $values);

        $values = $fallbackPool->getValues();

        $this->assertInternalType('array', $values);
        $this->assertCount(3, $values); // 2 classes + Doctrine namespace
        $this->assertArrayHasKey('%5BSymfony%5CBundle%5CFrameworkBundle%5CTests%5CFixtures%5CSerialization%5CPerson%5D%5B1%5D', $values);
        $this->assertArrayHasKey('%5BSymfony%5CBundle%5CFrameworkBundle%5CTests%5CFixtures%5CSerialization%5CAuthor%5D%5B1%5D', $values);
    }

    public function testWarmUpWithoutLoader()
    {
        $file = sys_get_temp_dir().'/cache-serializer-without-loader.php';
        @unlink($file);

        $fallbackPool = new ArrayAdapter();

        $warmer = new SerializerCacheWarmer(new LoaderChain(array()), $file, $fallbackPool);
        $warmer->warmUp(dirname($file));

        $this->assertFileExists($file);

        $values = require $file;

        $this->assertInternalType('array', $values);
        $this->assertCount(0, $values);

        $values = $fallbackPool->getValues();

        $this->assertInternalType('array', $values);
        $this->assertCount(0, $values);
    }
}
