<?php


namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

class PayDTO
{
    /**
     * @Serializer\Type("bool")
     */
    private $success;

    /**
     * @Serializer\Type("string")
     */
    private $type;

    /**
     * @Serializer\Type("string")
     */
    private $expiresAt;

    public function __construct(bool $success, string $type, ?string $expiresAt)
    {
        $this->success = $success;
        $this->type = $type;
        $this->expiresAt = $expiresAt;
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(string $success): void
    {
        $this->success = $success;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?string $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }
}