<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Tests\Adapter;

use Cache\IntegrationTests\CachePoolTest;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\OpCacheAdapter;

class OpCacheAdapterTest extends CachePoolTest
{
    protected $skippedTests = array(
        'testBasicUsage' => 'OpCacheAdapter is read-only.',
        'testClear' => 'OpCacheAdapter is read-only.',
        'testClearWithDeferredItems' => 'OpCacheAdapter is read-only.',
        'testDeleteItem' => 'OpCacheAdapter is read-only.',
        'testSaveExpired' => 'OpCacheAdapter is read-only.',
        'testSaveWithoutExpire' => 'OpCacheAdapter is read-only.',
        'testDeferredSave' => 'OpCacheAdapter is read-only.',
        'testDeferredSaveWithoutCommit' => 'OpCacheAdapter is read-only.',
        'testDeleteItems' => 'OpCacheAdapter is read-only.',
        'testDeleteDeferredItem' => 'OpCacheAdapter is read-only.',
        'testCommit' => 'OpCacheAdapter is read-only.',
        'testSaveDeferredWhenChangingValues' => 'OpCacheAdapter is read-only.',
        'testSaveDeferredOverwrite' => 'OpCacheAdapter is read-only.',
        'testIsHitDeferred' => 'OpCacheAdapter is read-only.',

        'testExpiresAt' => 'OpCacheAdapter does not support expiration.',
        'testExpiresAtWithNull' => 'OpCacheAdapter does not support expiration.',
        'testExpiresAfterWithNull' => 'OpCacheAdapter does not support expiration.',
        'testDeferredExpired' => 'OpCacheAdapter does not support expiration.',
        'testExpiration' => 'OpCacheAdapter does not support expiration.',
    );

    private static $file;

    public static function setupBeforeClass()
    {
        self::$file = sys_get_temp_dir().'/symfony-cache/opcache/adapter-test.php';
    }

    protected function tearDown()
    {
        if (file_exists(self::$file)) {
            FilesystemAdapterTest::rmdir(sys_get_temp_dir().'/symfony-cache');
        }
    }

    public function createCachePool()
    {
        return new OpCacheAdapterWrapper(self::$file, new NullAdapter());
    }

    public function testStore()
    {
        $arrayWithRefs = array();
        $arrayWithRefs[0] = 123;
        $arrayWithRefs[1] =& $arrayWithRefs[0];

        $object = (object) array(
            'foo' => 'bar',
            'foo2' => 'bar2',
        );

        $expected = array(
            'null' => null,
            'serializedString' => serialize($object),
            'arrayWithRefs' => $arrayWithRefs,
            'object' => $object,
            'arrayWithObject' => array('bar' => $object),
        );

        $adapter = $this->createCachePool();
        $adapter->store($expected);

        foreach ($expected as $key => $value) {
            $this->assertSame(serialize($value), serialize($adapter->getItem($key)->get()), 'Warm up should create a PHP file that OPCache can load in memory');
        }
    }

    public function testStoredFile()
    {
        $expected = array(
            'integer' => 42,
            'float' => 42.42,
            'boolean' => true,
            'array_simple' => array('foo', 'bar'),
            'array_associative' => array('foo' => 'bar', 'foo2' => 'bar2'),
        );

        $adapter = $this->createCachePool();
        $adapter->store($expected);

        $actual = require self::$file;

        $this->assertSame($expected, $actual, 'Warm up should create a PHP file that OPCache can load in memory');
    }
}

class OpCacheAdapterWrapper extends OpCacheAdapter
{
    public function save(CacheItemInterface $item)
    {
        call_user_func(\Closure::bind(function () use ($item) {
            $this->values[$item->getKey()] = $item->get();
            $this->store($this->values);
            $this->initialize();
        }, $this, OpCacheAdapter::class));

        return true;
    }
}
