<?php

namespace SwAuth\Domain\Entities;

use DateTimeImmutable;
use SwAuth\Domain\ValueObjects\TokenHash;

class EmailVerificationToken extends ExpiringToken
{
}