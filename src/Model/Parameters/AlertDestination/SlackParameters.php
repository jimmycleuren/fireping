<?php
declare(strict_types=1);

namespace App\Model\Parameters\AlertDestination;

use App\Model\Parameters\DynamicParameters;
use App\Model\Parameters\DynamicParametersInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class SlackParameters extends DynamicParameters
{
    /**
     * @var string|null
     */
    private $channel;
    /**
     * @var UriInterface
     */
    private $url;

    private function __construct(?string $channel, UriInterface $url)
    {
        $this->channel = $channel;
        $this->url = $url;
    }

    public static function fromJsonString(string $json): DynamicParametersInterface
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
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
