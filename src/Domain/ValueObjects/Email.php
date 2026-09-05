<?php

namespace SwAuth\Domain\ValueObjects;

use InvalidArgumentException;

final class Email {
    private string $email;


    public function __construct(string $email)
    {
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address");
        }
        $this->email = strtolower($email);

    }

    public function __toString(): string
    {
        return $this->email;
    }
}