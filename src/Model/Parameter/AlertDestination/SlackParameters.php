<?php
declare(strict_types=1);

namespace App\Model\Parameter\AlertDestination;

use App\Model\Parameter\DynamicParameters;
use App\Model\Parameter\DynamicParametersInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SlackParameters extends DynamicParameters
{
    /**
     * @var string
     * @Assert\NotBlank()
     */
    protected $channel;
    /**
     * @var UriInterface
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $url;

    public function __construct(string $channel, UriInterface $url)
    {
        $this->channel = $channel;
        $this->url = $url;
    }

    public function asArray(): array
    {
        return [
            'channel' => $this->channel,
            'url'     => (string) $this->url,
        ];
    }

    public static function fromArray(array $in): DynamicParametersInterface
    {
        return new self((string) ($in['channel'] ?? ''), new Uri($in['url'] ?? ''));
    }
}
