<?php
declare(strict_types=1);

namespace App\Model\ProbeArgument;

class HttpArguments extends ProbeArguments
{
    /**
     * @var bool
     */
    private $allowRedirectsEnabled;
    /**
     * @var array
     */
    private $allowRedirectOptions;

    private function __construct(bool $allowRedirectsEnabled, array $allowRedirectOptions)
    {
        $this->allowRedirectsEnabled = $allowRedirectsEnabled;
        $this->allowRedirectOptions = $allowRedirectOptions;
    }

    public static function fromJsonString(string $json): ProbeArgumentsInterface
    {
        return new self(true, ['someArg' => 'ok']);
    }

    public function asArray(): array
    {
        return ['allowRedirectsEnabled' => $this->allowRedirectsEnabled, 'allowRedirectOptions' => $this->allowRedirectOptions];
    }
}