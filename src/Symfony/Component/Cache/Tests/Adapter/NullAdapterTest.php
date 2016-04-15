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

class NullAdapterTest extends CachePoolTest
{
    protected $skippedTests = array(
        'testBasicUsage' => 'NullAdapter does not store anything',
        'testClearWithDeferredItems' => 'NullAdapter does not store anything',
        'testSaveExpired' => 'NullAdapter does not store anything',
        'testSaveWithoutExpire' => 'NullAdapter does not store anything',
        'testDeferredSaveWithoutCommit' => 'NullAdapter does not store anything',
        'testDeleteDeferredItem' => 'NullAdapter does not store anything',
        'testSaveDeferredWhenChangingValues' => 'NullAdapter does not store anything',
        'testSaveDeferredOverwrite' => 'NullAdapter does not store anything',
        'testSavingObject' => 'NullAdapter does not store anything',
        'testKeyLength' => 'NullAdapter does not store anything',
        'testDataTypeObject' => 'NullAdapter does not store anything',
        'testDataTypeString' => 'NullAdapter does not store anything',
        'testDataTypeInteger' => 'NullAdapter does not store anything',
        'testDataTypeNull' => 'NullAdapter does not store anything',
        'testDataTypeFloat' => 'NullAdapter does not store anything',
        'testDataTypeBoolean' => 'NullAdapter does not store anything',
        'testDataTypeArray' => 'NullAdapter does not store anything',
        'testIsHitDeferred' => 'NullAdapter does not store anything',
        'testExpiresAt' => 'NullAdapter does not store anything',
        'testExpiresAtWithNull' => 'NullAdapter does not store anything',
        'testExpiresAfterWithNull' => 'NullAdapter does not store anything',
        'testDeferredExpired' => 'NullAdapter does not store anything',
        'testExpiration' => 'NullAdapter does not store anything',
    );

    public function createCachePool()
    {
        return new NullAdapter();
    }

    public function testGetItem()
    {
        $adapter = $this->createCachePool();

        $item = $adapter->getItem('key');
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get(), "Item's value must be null when isHit is false.");
    }

    public function testHasItem()
    {
        $this->assertFalse($this->createCachePool()->hasItem('key'));
    }

    public function testGetItems()
    {
        $adapter = $this->createCachePool();

        $keys = array('foo', 'bar', 'baz', 'biz');

        /** @var CacheItemInterface[] $items */
        $items = $adapter->getItems($keys);
        $count = 0;

        foreach ($items as $key => $item) {
            $itemKey = $item->getKey();

            $this->assertEquals($itemKey, $key, 'Keys must be preserved when fetching multiple items');
            $this->assertTrue(in_array($key, $keys), 'Cache key can not change.');
            $this->assertFalse($item->isHit());

            // Remove $key for $keys
            foreach ($keys as $k => $v) {
                if ($v === $key) {
                    unset($keys[$k]);
                }
            }

            ++$count;
        }

        $this->assertSame(4, $count);
    }

    public function testIsHit()
    {
        $adapter = $this->createCachePool();

        $item = $adapter->getItem('key');
        $this->assertFalse($item->isHit());
    }

    public function testClear()
    {
        $this->assertTrue($this->createCachePool()->clear());
    }

    public function testDeleteItem()
    {
        $this->assertTrue($this->createCachePool()->deleteItem('key'));
    }

    public function testDeleteItems()
    {
        $this->assertTrue($this->createCachePool()->deleteItems(array('key', 'foo', 'bar')));
    }

    public function testSave()
    {
        $adapter = $this->createCachePool();

        $item = $adapter->getItem('key');
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get(), "Item's value must be null when isHit is false.");

        $this->assertFalse($adapter->save($item));
    }

    public function testDeferredSave()
    {
        $adapter = $this->createCachePool();

        $item = $adapter->getItem('key');
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get(), "Item's value must be null when isHit is false.");

        $this->assertFalse($adapter->saveDeferred($item));
    }

    public function testCommit()
    {
        $adapter = $this->createCachePool();

        $item = $adapter->getItem('key');
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get(), "Item's value must be null when isHit is false.");

        $this->assertFalse($adapter->saveDeferred($item));
        $this->assertFalse($this->createCachePool()->commit());
    }
}
