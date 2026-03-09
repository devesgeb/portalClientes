<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
         $this->helpers = ['form', 'url','assets'];




        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }


    public function assets_url(){

     $config= [
                  'asset_path' => ['public/assets/'],
                  'css_path' =>[ 'public/assets/css/'],         
                  'js_path' => ['public/assets/js/'],
                  'img_path' => ['public/assets/img/'],

              ];

        foreach ($config  as $key => $value) {

            if ($key =='css_path'){
                 $rutas['css_path'] = $value;
            }elseif ($key == 'js_path') {
                 $rutas['js_path'] = $value;
            }elseif ($key == 'asset_path') {
                 $rutas['asset_path'] = $value;
            }elseif ($key == 'img_path') { 
                 $rutas['img_path'] = $value;
            }      
}
/*
    $config['asset_path']       = 'public/assets/';
    $config['css_path']         = 'public/assets/css/';
    $config['download_path']    = 'public/assets/download/';
    $config['less_path']        = 'public/assets/less/';
    $config['js_path']          = 'public/assets/js/';
    $config['img_path']         = 'public/assets/img/';
    $config['swf_path']         = 'public/assets/swf/';
    $config['upload_path']      = 'public/assets/upload/';
    $config['xml_path']         = 'public/assets/xml/';
*/
    return $rutas; 

    }
}