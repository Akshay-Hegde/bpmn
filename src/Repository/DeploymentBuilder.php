<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace KoolKode\BPMN\Repository;

use KoolKode\Stream\ResourceInputStream;
use KoolKode\Stream\StringStream;
use Psr\Http\Message\StreamInterface;

/**
 * Builds a deployment from any number of resources.
 * 
 * @author Martin Schröder
 */
class DeploymentBuilder implements \Countable, \IteratorAggregate
{
    protected $name;

    protected $fileExtensions = [
        'bpmn'
    ];

    protected $resources = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function count(): int
    {
        return \count($this->resources);
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->resources);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add a file extension that shoul be parsed for BPMN 2.0 process definitions.
     * 
     * The deployment mechanism will parse ".bpmn" files by default.
     */
    public function addExtensions($extensions): self
    {
        $this->fileExtensions = \array_unique(\array_merge($this->fileExtensions, \array_map('strtolower', (array) $extensions)));
        
        return $this;
    }

    /**
     * Check if the given file will be parsed for BPMN 2.0 process definitions.
     */
    public function isProcessResource(string $name): bool
    {
        return \in_array(\strtolower(\pathinfo($name, \PATHINFO_EXTENSION)), $this->fileExtensions);
    }

    /**
     * Add a resource to the deployment.
     * 
     * @param string $name Local path and filename of the resource within the deployment.
     * @param mixed $resource Deployable resource (file), that can be loaded using a stream.
     */
    public function addResource(string $name, $resource): self
    {
        if ($resource instanceof StreamInterface) {
            $in = $resource;
        } elseif (\is_resource($resource)) {
            $in = new ResourceInputStream($resource);
        } else {
            $resource = (string) $resource;
            
            if (\preg_match("'^/|(?:[^:\\\\/]+://)|(?:[a-z]:[\\\\/])'i", $resource)) {
                $in = ResourceInputStream::fromUrl($resource);
            } else {
                $in = new StringStream($resource);
            }
        }
        
        $this->resources[\trim(\str_replace('\\', '/', $name), '/')] = $in;
        
        return $this;
    }

    /**
     * Add a ZIP archives file contents to the deployment.
     * 
     * @throws \InvalidArgumentException When the given archive could not be found.
     * @throws \RuntimeException When the given archive could not be read.
     */
    public function addArchive(string $file): self
    {
        if (!\is_file($file)) {
            throw new \InvalidArgumentException(\sprintf('Archive not found: "%s"', $file));
        }
        
        if (!\is_readable($file)) {
            throw new \RuntimeException(\sprintf('Archive not readable: "%s"', $file));
        }
        
        $zip = new \ZipArchive();
        $zip->open($file);
        
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = (array) $zip->statIndex($i);
                
                // This will skip empty files as well... need a better way to this eventually.
                if (empty($stat['size'])) {
                    continue;
                }
                
                $name = $zip->getNameIndex($i);
                
                // Cap memory at 256KB to allow for large deployments when necessary.
                $stream = new StringStream('', 262144);
                $resource = $zip->getStream($name);
                
                try {
                    while (!\feof($resource)) {
                        $stream->write(\fread($resource, 8192));
                    }
                    
                    $stream->rewind();
                } finally {
                    @\fclose($resource);
                }
                
                $this->resources[\trim(\str_replace('\\', '/', $name), '/')] = $stream;
            }
        } finally {
            @$zip->close();
        }
        
        return $this;
    }

    /**
     * (Recursively) add a all files from the given directory to the deployment, paths will be relative to the root directory.
     * 
     * @throws \InvalidArgumentException When the given directory was not found.
     * @throws \RuntimeException When the given directory is not readable.
     */
    public function addDirectory(string $dir): self
    {
        $base = @\realpath($dir);
        
        if (!\is_dir($base)) {
            throw new \InvalidArgumentException(\sprintf('Directory not found: "%s"', $dir));
        }
        
        if (!\is_readable($base)) {
            throw new \RuntimeException(\sprintf('Directory not readable: "%s"', $dir));
        }
        
        foreach ($this->collectFiles($base, '') as $name => $file) {
            $this->resources[\trim(\str_replace('\\', '/', $name), '/')] = ResourceInputStream::fromUrl($file);
        }
        
        return $this;
    }

    /**
     * Collect all files from the directory, uses recursion to grab files from sub-directories.
     */
    protected function collectFiles(string $dir, string $basePath): array
    {
        $files = [];
        $dh = \opendir($dir);
        
        try {
            while (false !== ($entry = \readdir($dh))) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                
                $check = $dir . \DIRECTORY_SEPARATOR . $entry;
                
                if (\is_dir($check)) {
                    foreach ($this->collectFiles($check, $basePath . '/' . $entry) as $k => $v) {
                        $files[$k] = $v;
                    }
                } elseif (\is_file($check)) {
                    $files[$basePath . '/' . $entry] = $check;
                }
            }
            
            return $files;
        } finally {
            \closedir($dh);
        }
    }
}
