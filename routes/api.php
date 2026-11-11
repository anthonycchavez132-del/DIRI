<?php
declare(strict_types=1);
use App\Controllers\ApiController;

$router->get('/api/v1/tramites',[ApiController::class,'search']);
$router->get('/api/v1/tramite',[ApiController::class,'detail']);
$router->get('/api/v1/atencion',[ApiController::class,'attention']);
