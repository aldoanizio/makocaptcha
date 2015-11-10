<?php

/**
 * @copyright  Aldo Anizio Lugão Camacho
 * @license    http://www.makoframework.com/license
 */

namespace aldoanizio\makocaptcha;


// Mako
use \mako\config\Config;
use \mako\session\Session;
use \mako\security\crypto\CryptoManager;


// Capcha Builder
use Gregwar\Captcha\CaptchaBuilder;


/**
 * MakoCaptcha.
 *
 * @author  Aldo Anizio Lugão Camacho
 */

class MakoCaptcha
{
    //---------------------------------------------
    // Class properties
    //---------------------------------------------

    /**
     * Config instance.
     *
     * @var \mako\core\Config
     */

    protected $config;

    /**
     * Session instance.
     *
     * @var \mako\session\Session
     */

    protected $session;

    /**
     * CryptoManager instance
     *
     * @var mako\security\crypto\CryptoManager
     */

    protected $encrypter;

    /**
     * Captcha Builder Instance
     *
     * @var \Gregwar\Captcha\CaptchaBuilder
     */

    protected $builder;

    /**
     * Session secret
     *
     * @var string
     */

    protected $secret;

    /**
     * Generated Phrase
     *
     * @var string
     */

    protected $phrase;

    //---------------------------------------------
    // Class constructor, destructor etc ...
    //---------------------------------------------

    /**
     * Constructor.
     *
     * @access  public
     * @param   Config         $config   Config instance
     * @param   Session        $session  Session instance
     * @param   CryptoManager  $crypto   Crypto Manager
     */

    public function __construct(Config $config, Session $session, CryptoManager $crypto)
    {
        // Config Instance

        $this->config = $config;

        // Session Instance

        $this->session = $session;

        // CryptoManager instance

        $this->encrypter = $crypto->instance();

        // Session Instance

        $this->setSecret($this->config->get('makocaptcha::config.secret'));

        // Builder options

        $width  = $this->config->get('makocaptcha::config.width');
        $height = $this->config->get('makocaptcha::config.height');
        $font   = $this->config->get('makocaptcha::config.font');

        // Set captcha builder

        $this->setBuilder($width, $height, $font);
    }

    //---------------------------------------------
    // Class methods
    //---------------------------------------------

    /**
     * Load connection parameters from config file.
     *
     * @access  private
     * @param   string  $connection  Connection group name
     */

    private function setBuilder($width = 150, $height = 40, $font = null)
    {
        if($this->session->has($this->secret . '::phrase') && $this->isAlive())
        {
            // Get phrase saved in session

            $sessionPhrase = $this->session->get($this->secret . '::phrase');

            // Decrypt phrase

            $phrase = $this->encrypter->decrypt($sessionPhrase);

            // Captcha builder

            $this->builder = new CaptchaBuilder($phrase);

            $this->builder->setDistortion(false);
            $this->builder->setMaxBehindLines(0);
            $this->builder->setMaxFrontLines(0);
            $this->builder->setIgnoreAllEffects(false);
            $this->builder->setBackgroundColor(241, 241, 241);

            $this->builder->build($width, $height, $font);
        }
        else
        {
            // Captcha builder

            $this->builder = new CaptchaBuilder();

            $this->builder->setDistortion(false);
            $this->builder->setMaxBehindLines(0);
            $this->builder->setMaxFrontLines(0);
            $this->builder->setIgnoreAllEffects(false);
            $this->builder->setBackgroundColor(241, 241, 241);

            $this->builder->build($width, $height, $font);

            // Set last generated phrase

            $this->session->put($this->secret . '::phrase', $this->encrypter->encrypt($this->builder->getPhrase()));

            // Set last modified time

            $this->session->put($this->secret . '::lastModified', time());
        }
    }

    /**
     * Get generated phrase
     *
     * @access  private
     * @return  string
     */

    private function isAlive()
    {
        // Get how many times the captcha stil valid

        $timeToLive = $this->config->get('makocaptcha::config.ttl');

        // Validate time

        $lastModified = $this->session->get($this->secret . '::lastModified', null);

        if(is_null($lastModified) || $lastModified < (time() - $timeToLive))
        {
            return false;
        }

        return true;
    }

    /**
     * Set secret key
     *
     * @access  public
     * @return  string
     */

    public function setSecret($secret)
    {
        $this->secret = md5($secret);
    }

    /**
     * Get generated phrase
     *
     * @access  public
     * @return  string
     */

    public function regenerate($width = null, $height = null, $font = null)
    {
        // Builder options

        $width  = is_null($width)  ? $this->config->get('makocaptcha::config.width')  : $width;
        $height = is_null($height) ? $this->config->get('makocaptcha::config.height') : $height;
        $font   = is_null($font)   ? $this->config->get('makocaptcha::config.font')   : $font;

        // Destroy session

        $this->session->remove($this->secret . '::phrase');
        $this->session->remove($this->secret . '::lastModified');

        // Regenerate builder

        $this->setBuilder($width, $height, $font);
    }

    /**
     * Get generated phrase
     *
     * @access  public
     * @return  string
     */

    public function getPhrase()
    {
        return $this->builder->getPhrase();
    }

    /**
     * Saves the captcha into a jpeg in the $filename, with the given quality
     *
     * @access  public
     * @param   string  $filename  Target filename
     * @param   string  $quality   Image quality
     * @return  void
     */

    public function save($filename, $quality = 90)
    {
        $this->builder->save($filename, $quality);
    }

    /**
     * Gets the image contents
     *
     * @access  public
     * @param   string  $quality  Image quality
     * @return  void
     */

    public function get($quality = 90)
    {
        return $this->builder->get($quality);
    }

    /**
     * Gets the HTML inline base64
     *
     * @access  public
     * @param   string  $quality  Image quality
     * @return  void
     */

    public function inline($quality = 90)
    {
        return $this->builder->inline($quality);
    }

    /**
     * Outputs the image
     *
     * @access  public
     * @param   string  $quality  Image quality
     * @return  void
     */

    public function output($quality = 90)
    {
        return $this->builder->output($quality);
    }

    /**
     * Validate input phrase
     *
     * @access  public
     * @param   array   $userInput  User input phrase
     * @return  string
     */

    public function validate($userInput)
    {
        // Validate time

        if(!$this->isAlive())
        {
            // Regenerate builder

            $this->regenerate();

            return false;
        }

        // Validate using builder

        return $this->builder->testPhrase($userInput);
    }
}