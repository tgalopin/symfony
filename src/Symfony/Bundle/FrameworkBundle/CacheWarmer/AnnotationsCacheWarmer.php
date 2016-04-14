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

/**
 * Generates annotations cache file.
 * Uses the Composer classmap file to find classes.
 * Dump the composer optimized classmap to take full advantage of the resulting cache.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AnnotationsCacheWarmer implements CacheWarmerInterface
{
    private $annotationReader;
    private $opCacheFile;
    private $fallbackPool;

    /**
     * @param Reader                 $annotationReader
     * @param string                 $opCacheFile      The PHP file where annotations are cached.
     * @param CacheItemPoolInterface $fallbackPool     The pool where runtime-discovered annotations are cached.
     */
    public function __construct(Reader $annotationReader, $opCacheFile, CacheItemPoolInterface $fallbackPool)
    {
        $this->annotationReader = $annotationReader;
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
        $annotatedClassMap = $cacheDir.'/annotations.map';

        if (!is_file($annotatedClassMap) || !($classMap = $this->loadComposerClassMap())) {
            $adapter->store(array());

            return;
        }

        $annotatedClassMap = include $annotatedClassMap;

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

    private function loadComposerClassMap()
    {
        $classMap = array();

        foreach (spl_autoload_functions() as $function) {
            if (!is_array($function)) {
                continue;
            }

            if ($function[0] instanceof DebugClassLoader) {
                $function = $function[0]->getClassLoader();
            }

            if (is_array($function) && $function[0] instanceof ClassLoader) {
                $classMap += $function[0]->getClassMap();
            }
        }

        return array_keys($classMap);
    }

    private function isAnnotatedClassToCache($class, array $annotatedClasses)
    {
        $blacklisted = false !== strpos($class, 'Test');

        foreach ($annotatedClasses as $annotatedClass) {
            if ($blacklisted && false === strpos($annotatedClass, 'Test')) {
                continue;
            }

            if (false !== strpos('\\'.$class, $annotatedClass)) {
                return true;
            }
        }

        return false;
    }

    private function readAllComponents(Reader $reader, $class)
    {
        $reflectionClass = new \ReflectionClass($class);
        $reader->getClassAnnotations($reflectionClass);

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $reader->getMethodAnnotations($reflectionMethod);
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $reader->getPropertyAnnotations($reflectionProperty);
        }
    }
}
