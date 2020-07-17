<?php
declare(strict_types=1);

namespace App\Model\Parameters\AlertDestination;

use App\Model\Parameters\JsonParameters;
use App\Model\Parameters\JsonParametersInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class SlackParameters extends JsonParameters
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

    public static function fromJsonString(string $json): JsonParametersInterface
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

    public static function fromArray(array $in): JsonParametersInterface
    {
        return new self($in['channel'] ?? null, new Uri($in['url'] ?? ''));
    }
}
