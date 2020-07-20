<?php
declare(strict_types=1);

namespace App\Model\Parameter\AlertDestination;

use App\Model\Parameter\DynamicParameters;
use App\Model\Parameter\DynamicParametersInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class SlackParameters extends DynamicParameters
{
    /**
     * @var string|null
     */
    protected $channel;
    /**
     * @var UriInterface
     */
    protected $url;

    public function __construct(?string $channel, UriInterface $url)
    {
        $this->channel = $channel;
        $this->url = $url;
    }

    public function asArray(): array
    {
        return [
            'channel' => $this->channel,
            'url' => (string) $this->url,
        ];
    }

    public static function fromArray(array $in): DynamicParametersInterface
    {
        return new self($in['channel'] ?? null, new Uri($in['url'] ?? ''));
    }
}
