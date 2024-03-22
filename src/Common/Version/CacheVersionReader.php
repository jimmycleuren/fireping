<?php

declare(strict_types=1);

namespace App\Common\Version;

use Symfony\Component\Cache\Adapter\AdapterInterface;

class CacheVersionReader implements VersionReaderInterface
{
    public function __construct(private readonly VersionReaderInterface $reader, private readonly AdapterInterface $adapter)
    {
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