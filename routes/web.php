<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

#$otTracer = new \DDTrace\OpenTracer\Tracer(\DDTrace\GlobalTracer::get());
#\OpenTracing\GlobalTracer::set($otTracer);


Route::get('/', function () {
  $otTracer = \DDTrace\GlobalTracer::get();
  $visited = DB::select('select * from places where visited = ?', [1]);
  $togo = DB::select('select * from places where visited = ?', [0]);

  $scope = $otTracer->startActiveSpan('redis.command');
  $scope->getSpan()->type = 'cache';
  $scope->getSpan()->resource = 'INCR';
  $scope->getSpan()->service = 'redis';
  $scope->getSpan()->setTag('redis.raw_command','INCR visitantes');
  $redis = Redis::connection();
  $redis->incr('visitantes');
  $scope->close();

  return view('travellist', ['visited' => $visited, 'togo' => $togo] );
})->name('/');


Route::get('/redis', function () {
  $otTracer = \DDTrace\GlobalTracer::get();

  $scope = $otTracer->startActiveSpan('redis.command');
  $span = $scope->getSpan();
  $span->type = 'cache';
  $span->resource = 'GET';
  $span->service = 'redis';
  $span->setTag('redis.raw_command','GET visitantes');

  $redis = Redis::connection();
  $visitantes = $redis->get('visitantes');
  $scope->close();
  
  return view('redis', ['visitantes' => $visitantes] );
})->name('/redis');

