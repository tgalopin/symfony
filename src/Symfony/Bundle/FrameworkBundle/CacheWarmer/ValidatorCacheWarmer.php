<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\CacheWarmer;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\OpCacheAdapter;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\Cache\DoctrineProvider;
use Symfony\Component\Debug\DebugClassLoader;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Generates validator metadata mapping cache file.
 * Uses configured ValidatorBuilder mappings.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ValidatorCacheWarmer implements CacheWarmerInterface
{
    private $opCacheFile;
    private $fallbackPool;

    private $xmlMappings = array();
    private $yamlMappings = array();
    private $methodMappings = array();

    /**
     * @param string                 $opCacheFile      The PHP file where annotations are cached.
     * @param CacheItemPoolInterface $fallbackPool     The pool where runtime-discovered annotations are cached.
     */
    public function __construct($opCacheFile, CacheItemPoolInterface $fallbackPool)
    {
        $this->opCacheFile = $opCacheFile;
        if (!$fallbackPool instanceof AdapterInterface) {
            $fallbackPool = new ProxyAdapter($fallbackPool);
        }
        $this->fallbackPool = $fallbackPool;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $adapter = new OpCacheAdapter($this->opCacheFile, $this->fallbackPool);

        VarDumper::dump($this->xmlMappings);
        VarDumper::dump($this->yamlMappings);
        VarDumper::dump($this->methodMappings);
        exit;

        $arrayPool = new ArrayAdapter(0, false);
        $reader = new CachedReader($this->annotationReader, new DoctrineProvider($arrayPool));

        foreach ($classMap as $class) {
            if ($this->isAnnotatedClassToCache($class, $annotatedClassMap)) {
                $this->readAllComponents($reader, $class);
            }
        }

        $values = $arrayPool->getValues();
        $adapter->store($values);

        $this->fallbackPool->clear();
        foreach ($values as $k => $v) {
            $item = $this->fallbackPool->getItem($k);
            $this->fallbackPool->saveDeferred($item->set($v));
        }
        $this->fallbackPool->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function addXmlMappings(array $paths)
    {
        $this->xmlMappings = array_merge($this->xmlMappings, $paths);
    }

    /**
     * {@inheritdoc}
     */
    public function addYamlMappings(array $paths)
    {
        $this->yamlMappings = array_merge($this->yamlMappings, $paths);
    }

    /**
     * {@inheritdoc}
     */
    public function addMethodMapping($methodName)
    {
        $this->methodMappings[] = $methodName;
    }
}
