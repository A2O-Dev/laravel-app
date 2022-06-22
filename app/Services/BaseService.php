<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class BaseService {
    /**
     * @var MessageBag
     */
    protected $errors;

    /**
     * BaseService constructor.
     */
    public function __construct() {
        $this->errors = new MessageBag();
    }

    public function hasErrors() {
        return $this->errors->any();
    }

    public function getErrors() {
        return $this->errors;
    }

    public function clearErrors() {
        $this->errors = new MessageBag();
    }

    /**
     * @return string
     */
    public function getErrorsAsPlainString() {
        $messageList = $this->getErrors()->getMessages();
        $errorMessageList = [];
        foreach ($messageList as $key => $message) {
            $errorMessageList[] = "$key: " . implode(', ', $message);
        }
        return implode(', ', $errorMessageList);
    }

    /**
     * @return mixed
     */
    public function getLastTotalCount() {
        return collect(DB::select(DB::raw('SELECT FOUND_ROWS() as totalCount')))
            ->pluck('totalCount')
            ->max();
    }
}
