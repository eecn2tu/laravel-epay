<?php
namespace Sinta\LaravelEpay;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;


/**
 * 收付直通车代收服务类
 *
 * Class EPayServiceProvider
 * @package Sinta\LaravelEpay
 */
class EPayServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->setupConfig();
    }


    protected function setupConfig()
    {
        $source_config = realpath(__DIR__.'/../config/laravel-epay.php');
        if($this->app instanceof LaravelApplication && $this->app->runningInConsole()){
            $this->publishes([
                $source_config => config_path('laravel-epay.php')
            ]);
        }elseif($this->app instanceof LumenApplication){
            $this->app->configure('laravel-epay.php');
        }
        $this->mergeConfigFrom($source_config,'');

    }

    /**
     * 注册服务
     */
    public function register()
    {
        $this->app->singleton('epay.easypay',function($app){
            return new \Sinta\LaravelEpay\Pay\Easypay($app->config->get('laravel-epay'));
        });

        $this->app->singleton('epay.gatepay',function($app){
            return new \Sinta\LaravelEpay\Pay\Gatepay($app->config->get('laravel-epay'));
        });

        $this->app->singleton('epay.daipay',function($app){
            return new \Sinta\LaravelEpay\Pay\Daipay($app->config->get('laravel-epay'));
        });
    }

    /**
     * 提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return [
            'epay.easypay',
            'epay.gatepay',
            'epay.daipay'
        ];
    }
}