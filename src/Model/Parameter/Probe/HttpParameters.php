<?php
declare(strict_types=1);

namespace App\Model\Parameter\Probe;

use App\Model\Parameter\DynamicParameters;
use App\Model\Parameter\DynamicParametersInterface;

class HttpParameters extends DynamicParameters
{
    /**
     * @var string|null
     */
    protected $host;
    /**
     * @var string|null
     */
    protected $path;
    /**
     * @var string|null
     */
    protected $protocol;

    public function __construct(?string $host, ?string $path, ?string $protocol)
    {
        $this->host = $host;
        $this->path = $path;
        $this->protocol = $protocol;
    }

    public function asArray(): array
    {
        return [
            'host'     => $this->host,
            'path'     => $this->path,
            'protocol' => $this->protocol
        ];
    }

    public static function fromArray(array $in): DynamicParametersInterface
    {
        return new self($in['host'] ?? null, $in['path'] ?? null, $in['protocol'] ?? null);
    }
}
