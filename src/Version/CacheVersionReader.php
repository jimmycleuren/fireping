<?php

declare(strict_types=1);

namespace App\Version;

use Symfony\Component\Cache\Adapter\AdapterInterface;

class CacheVersionReader implements VersionReaderInterface
{
    /**
     * @var VersionReaderInterface
     */
    private $reader;
    /**
     * @var AdapterInterface
     */
    private $adapter;

    public function __construct(VersionReaderInterface $reader, AdapterInterface $adapter)
    {
        $this->reader = $reader;
        $this->adapter = $adapter;
    }

    public function version(): Version
    {
        $appVersion = $this->adapter->getItem('app.version');
        if (!$appVersion->isHit()) {
            $appVersion->set($this->reader->version());
            $this->adapter->save($appVersion);
        }

        return $appVersion->get();
    }
}