<?php

/**
 * @copyright  Aldo Anizio Lugão Camacho
 * @license    http://www.makoframework.com/license
 */

namespace aldoanizio\makocaptcha;


// MakoCaptcha package

use \aldoanizio\makocaptcha\MakoCaptcha;


/**
 * MakoCaptcha package.
 *
 * @author  Aldo Anizio Lugão Camacho
 */

class MakoCaptchaPackage extends \mako\application\Package
{
    /**
     * Package name.
     *
     * @var string
     */

    protected $packageName = 'aldoanizio/makocaptcha';

    /**
     * Package namespace.
     *
     * @var string
     */

    protected $fileNamespace = 'makocaptcha';

    /**
     * Register the service.
     *
     * @access  protected
     */

    protected function bootstrap()
    {
        $this->container->registerSingleton(['aldoanizio\makocaptcha\MakoCaptcha', 'makoCaptcha'], function($container)
        {
            return new MakoCaptcha($container->get('config'), $container->get('session'), $container->get('crypto'));
        });
    }
}