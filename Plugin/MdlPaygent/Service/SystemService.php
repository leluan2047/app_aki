<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Service;


use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Eccube\Application;
use Eccube\Common\Constant;

/**
 * システムモジュール基本クラス
 */
class SystemService
{
    private $verExit = array("3.0.0","3.0.1","3.0.2","3.0.3","3.0.4","3.0.5","3.0.6","3.0.7","3.0.8","3.0.9","3.0.10" );
    
    public function __construct(\Eccube\Application $app)
    {
        $this->version = Constant::VERSION;
    }
    
    function isVerExit($var){
        if(in_array($var, $this->verExit)){
            return true;
        }
        return false;
    }
    
    
    
    /**
     * コンストラクタ
     *
     * @return void
     */
    function SystemService()
    {
        
    }
    
    function procExitResponse($url = null, $response = null){
        if($this->isVerExit($this->version)){
            if(!is_null($url)){
                header("Location: " . $url);
            }
            exit;
        }
        
        return $response;
    }
    
    
    function procExit($url = null, $app = null){
        if($this->isVerExit($this->version)){
            if(!is_null($url)){
                header("Location: " . $url);
            }
            exit;
        }
        if(is_null($app)){
            $response = $app->redirect($url);
        } else {
            $response = $app->redirect($url);
        }
        return $response;
    }
    
}