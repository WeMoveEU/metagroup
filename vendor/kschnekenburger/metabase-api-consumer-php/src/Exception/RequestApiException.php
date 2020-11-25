<?php
namespace KenSh\MetabaseApi\Exception;

class RequestApiException extends \Exception //implements MetabaseApiExceptionInterface
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
