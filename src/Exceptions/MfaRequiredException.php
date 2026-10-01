<?php

namespace SwAuth\Exceptions;

use Exception;

class MfaRequiredException extends Exception {

    private readonly int $userId;
    public string $message;
    public function __construct(int $userId, $message = "MFA Obrigatório"){
        $this->userId = $userId;
        $this->message = $message;
    }

    public function getUserId(){
        return $this->userId;
    }



}