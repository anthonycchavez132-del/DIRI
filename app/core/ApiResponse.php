<?php

declare(strict_types=1);
namespace App\Core;
class ApiResponse
{
    public static function send($data,int $status=200,?array $error=null): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['version'=>'1','data'=>$data,'error'=>$error],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_THROW_ON_ERROR);
    }
    public static function error(int $status,string $code,string $message): void
    {
        self::send(null,$status,['code'=>$code,'message'=>$message]);
    }
}
