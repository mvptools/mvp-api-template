<?php

namespace App\Exceptions;

use Exception;
use Nuwave\Lighthouse\Exceptions\RendersErrorsExtensions;

class AuthException extends Exception implements RendersErrorsExtensions
{
    /**
     * The reason for the exception being thrown
     * 
     * @var string
     */
    protected $reason;

    /**
     * Constructor for the exception
     *
     * @param string $message
     * @param string $reason
     * @return void
     */
    public function __construct(string $message, string $reason)
    {
        parent::__construct($message);
        $this->reason = $reason;
    }

    /**
     * Whether the message is safe for the user to see.
     *
     * @return boolean
     */
    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * Category of the exception
     *
     * @return string
     */
    public function getCategory(): string
    {
        return 'auth';
    }

    /**
     * Content of the exception
     *
     * @return array
     */
    public function extensionsContent(): array
    {
        return [
            'reason' => $this->reason,
        ];
    }
}
