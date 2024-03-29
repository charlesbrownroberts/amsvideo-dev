<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @see Zend_Auth_Adapter_Interface
 */


/**
 * CAS Authentication Adapter
 *
 * Central Authentication Service project, more commonly referred to as CAS.  
 * CAS is an authentication system originally created by Yale University to 
 * provide a trusted way for an application to authenticate a user.
 *
 * @see http://www.jasig.org/cas
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


// Load the CAS lib
require_once APPLICATION_PATH . '/../library/vendors/CAS-1.3.2/CAS.php';


class App_Auth_Adapter_Cas implements Zend_Auth_Adapter_Interface
{
    
    /**
     * The client
     *
     * @var Zend_Http_Client
     */
    protected $_client;
    
    /**
     * The adapter used to perform the actual connection to the server
     *
     * @var Zend_Http_Client_Adapter_Interface
     */
    protected $_clientAdapter;
    
    /**
     * Error messages to return with @see Zend_Auth_Result
     *
     * @var array    $_errors
     */
    protected $_errors = array();
    
    /**
     * The CAS server hostname
     *
     * @var string    $_hostname
     */
    protected $_hostname;
    
    /**
     * The login URL
     *
     * @var string    $_loginUrl
     */
    protected $_loginUrl = 'https://testcas.csus.edu/csus.cas/login';
    
    /**
     * The logout URL
     *
     * @var string    $_logoutUrl
     */
    protected $_logoutUrl = 'https:// testcas.csus.edu/csus.cas/logout';
    
    /**
     * The CAS server URI path
     *
     * @var string    $_path
     */
    protected $_path = '';
    
    /**
     * The CAS server port
     *
     * @var integer    $_port
     */
    protected $_port = 443;
    
    /**
     * The CAS server protocol
     *
     * @var integer    $_protocol
     */
    protected $_protocol = 'https';

    /**
     * The service parameter
     *
     * @var string    $_serviceParam
     */
    protected $_serviceParam = 'service';

    /**
     * The service URL
     *
     * The service URL is where the client will be redirected after contacting 
     * the CAS server.
     *
     * @var string    $_service
     */
    protected $_service = 'http://amsvideo-dev/index/cas';
    
    /**
     * The array of query parameter
     *
     * @var array    $_queryParams
     */
    protected $_queryParams = array();
    
    /**
     * The ticket value
     *
     * @var string    $_ticket
     */
    protected $_ticket = '';
    
    /**
     * The ticket parameter
     *
     * @var string    $_ticketParam
     */
    protected $_ticketParam = 'ticket';
    
    /**
     * The validation parameter
     *
     * CAS 1: validate
     * CAS 2: serviceValidate
     * CAS 3: serviceValidate
     *
     * @var string    $_validationParam
     */
    protected $_validationParam = 'serviceValidate';
    
    /**
     * The URL to the CAS server
     *
     * @var string    $_url
     */
    protected $_url = '';
    
    /**
     * The XML name space for the CAS response
     *
     * @var string    $_xmlNameSpace
     */
    protected $_xmlNameSpace = '';
    
    /**
     * Create instance of the CAS Authenticator
     *
     * @param  mixed $options  An array or Zend_Config object with adapter parameters.
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif ($options instanceof Zend_Config) {
            $this->setConfig($options);
        }
    }

    /**
     * Set adapater state from options array
     *
     * @param  array $options
     * @return Zend_Auth_Adapter_Cas
     */
    public function setOptions(array $options)
    {
        $forbidden = array(
            'Options', 'Config'
        );

        foreach ($options as $key => $value) {
            $normalized = ucfirst($key);
            if (in_array($normalized, $forbidden)) {
                continue;
            }

            $method = 'set' . $normalized;
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        
        // Set the URL
        $url = $this->getUrl();
        
        if(empty($url)) {
            $this->setUrl();
        }
        
        // Set the service URL
        $service = $this->getService();
        
        if(empty($service)) {
            $this->setService();
        }
        
        // Set the login URL
        $loginUrl = $this->getLoginUrl();
        
        if(empty($loginUrl)) {
            $this->setLoginUrl();
        }

        // Set the logout URL
        $logoutUrl = $this->getLogoutUrl();
        
        if(empty($logoutUrl)) {
            $this->setLogoutUrl();
        }

        return $this;
    }

    /**
     * Set adapter from config object
     *
     * @param  Zend_Config $config
     * @return Zend_Auth_Adapter_Cas
     */
    public function setConfig(Zend_Config $config)
    {
        return $this->setOptions($config->toArray());
    }

    /**
     * Set adapter for client
     *
     * @param  array $client
     * @return Zend_Auth_Adapter_Cas
     */
    public function setClientAdapter($client = array())
    {
        extract($client);
        
        $adapter = isset($adapter) ? $adapter : 'Zend_Http_Client_Adapter_Socket';
        $options = isset($options) ? $options : array();
        
        $this->_clientAdapter = new $adapter;
        
        $this->_clientAdapter->setConfig($options);

        return $this;
    }

    /**
     * Set client
     *
     * @return Zend_Auth_Adapter_Cas
     */
    public function setClient()
    {
        $config = array(
            'adapter' => &$this->_clientAdapter
        );
        
        $this->_client = new Zend_Http_Client($this->getValidationURL(), $config);

        return $this;
    }

    /**
     * Returns the hostname
     * 
     * @return string
     */
    public function getHostname()
    {
        return $this->_hostname;
    }

    /**
     * Sets the hostname. Set as cas.example.org
     * 
     * @param string $hostname.
     */
    public function setHostname($hostname)
    {
        $this->_hostname = $hostname;
    }

    /**
     * Returns the path
     * 
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Sets the path. Removes leading slash.
     * 
     * @param string $path.
     */
    public function setPath($path)
    {
        $this->_path = (string) $path;
        $this->_path = (!empty($this->_path) && $this->_path[0] == '/') ? substr($this->_path, 1) : $this->_path;
    }

    /**
     * Returns the port
     * 
     * @return integer
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * Sets the port. The default is 443
     * 
     * @param integer $port.
     */
    public function setPort($port)
    {
        $port = (integer) $port;
        if (!empty($port)) {
            $this->_port = $port;
        }
    }

    /**
     * Returns the protocol
     * 
     * @return string
     */
    public function getProtocol()
    {
        return $this->_protocol;
    }

    /**
     * Sets the protocol. The default is https
     * 
     * @param string $value.
     */
    public function setProtocol($protocol)
    {
        $this->_protocol = (string) $protocol;
    }

    /**
     * Returns the URL to login to the CAS server.
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->_loginUrl;
    }

    /**
     * Sets the URL to login to the CAS server.
     *
     * @param string $url    The CAS url
     */
    public function setLoginUrl($url = '')
    {
        
        if(empty($url)) {
            
            $url = $this->getUrl();
            
            $url .= (substr($url, -1) == '/') ? 'login?' : '/login?';
            
            $url .= $this->getServiceParam() . '=' . $this->getService();
        }
        
        $this->_loginUrl = $url;
    }

    /**
     * Returns the URL to logout of the CAS server.
     *
     * @return string
     */
    public function getLogoutUrl()
    {
        return $this->_logoutUrl;
    }
    
    /**
     * Set the URL to logout of the CAS server.
     *
     * @param string $service The service url requesting logout.
     */
    public function setLogoutUrl($url = '')
    {
        
        if(empty($url)) {
            
            $url = $this->getUrl();
            
            $url .= (substr($url, -1) == '/') ? 'logout?' : '/logout?';
            
            $url .= $this->getServiceParam() . '=' . $this->getService();
        }
        
        $this->_logoutUrl = $url;
    }

    /**
     * Returns the query parameters
     * 
     * @return string
     */
    public function getQueryParams()
    {
        return $this->_queryParams;
    }

    /**
     * Sets the query parameter
     * 
     * @param array $param.
     */
    public function setQueryParams($param)
    {
        $this->_queryParams = $param;
    }

    /**
     * Get the service URL
     *
     * @return string
     */
    public function getService()
    {
        return $this->_service;
    }

    /**
     * Clears the service URL
     */
    public function clearService()
    {
        $this->_service = '';
    }

    /**
     * Set the service URL
     *
     * @param string $url   The url to set for service
     */
    public function setService($url = '')
    {
        if (empty($url)) {
            $url = $this->selfUrl();
        }

        $this->_service = $url;
    }

    /**
     * Returns the service parameter
     * 
     * @return string
     */
    public function getServiceParam()
    {
        return $this->_serviceParam;
    }

    /**
     * Sets the service parameter
     * 
     * @param string $param.
     */
    public function setServiceParam($param)
    {
        $this->_serviceParam = $param;
    }

    /**
     * Returns true if a ticket is not empty
     *
     * @return     boolean
     */
    public function hasTicket()
    {
        $ticket = $this->getTicket();
        return empty($ticket) ? false : true;
    }

    /**
     * Returns the ticket parameter
     * 
     * @return string
     */
    public function getTicket()
    {
        return $this->_ticket;
    }

    /**
     * Sets the CAS validation ticket
     *
     * @param string $ticket  Ticket to validate given by the CAS Server
     */
    public function setTicket($ticket = '')
    {
        
        if(!empty($ticket)) {
            $this->_ticket = $ticket;
        }
        elseif(isset($this->_queryParams[ $this->getTicketParam() ])) {
            $this->_ticket = $this->_queryParams[ $this->getTicketParam() ];
        }
        else {
            $this->_ticket = $ticket;
        }
    }

    /**
     * Returns the ticket parameter
     * 
     * @return string
     */
    public function getTicketParam()
    {
        return $this->_ticketParam;
    }

    /**
     * Sets the ticket parameter
     * 
     * @param string $param.
     */
    public function setTicketParam($param)
    {
        $this->_ticketParam = $param;
    }

    /**
     * Get the CAS URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Set URL to CAS server
     *
     * @param string $url   The url to set for the CAS server
     */
    public function setUrl($url = '')
    {
        if (empty($url)) {
            
            /*
             * Verify hostname has been set
             */
            
            $hostname = $this->getHostname();
            
            if (empty($hostname)) {
                /**
                 * @see Zend_Auth_Exception
                 */
                require_once 'Zend/Auth/Exception.php';
                throw new Zend_Auth_Exception('Hostname must be set.');
            }
            
            $path = $this->getPath();
            $path = (empty($path)) ? '' : '/' . $path;
            
            $port = $this->getPort();
            
            if($port != 443 && !empty($port)) {
                
                $port = ':' . $port;
            }
            else {
                $port = '';
            }
            
            $url = $this->getProtocol() . '://' . $hostname . $port . $path;
        }
        
        $this->_url = $url;
    }

    /**
     * Returns the ticket parameter
     * 
     * @return string
     */
    public function getValidationParam()
    {
        return $this->_validationParam;
    }

    /**
     * Sets the validation parameter
     * 
     * @param string $param.
     */
    public function setValidationParam($param)
    {
        $this->_validationParam = $param;
    }
    
    /**
     * Returns the array of validation parameters.
     *
     * @param string $ticket  Ticket to validate given by CAS Server
     * @param string $service URL to the service requesting authentication
     *
     * @return array
     */
    protected function getValidationParams($ticket, $service)
    {
        return array(
            $this->getServiceParam()    => $service,
            $this->getTicketParam()     => $ticket,
        );
    }

    /**
     * Returns the validation URL to get a ticket
     * 
     * @return string
     */
    public function getValidationURL()
    {
        $url = $this->getUrl();
        
        // Append a slash if not present
        $url .= (substr($url, -1) == '/') ? '' : '/';
        
        $url .= $this->_validationParam . '?';
        
        return $url;
    }

    /**
     * Returns the XML Name Space
     * 
     * @return string
     */
    public function getXmlNameSpace()
    {
        return $this->_xmlNameSpace;
    }

    /**
     * Sets the XML Name Space
     * 
     * @param string $param.
     */
    public function setXmlNameSpace($param)
    {
        $this->_xmlNameSpace = $param;
    }

    /**
     * Zend_Auth Authentication
     *
     * @param return boolean
     *
     * @return Zend_Auth_Result
     */ 
    public function authenticate()
    {        
		$phpcas_path = APPLICATION_PATH . '/../library/vendors/CAS-1.3.2/CAS';
		
		///////////////////////////////////////
		// Basic Config of the phpCAS client //
		///////////////////////////////////////
		
		// Full Hostname of your CAS Server
		$cas_host = 'testcas.csus.edu';
		
		// Context of the CAS Server
		$cas_context = '/csus.cas';
		
		// Port of your CAS server. Normally for a https server it's 443
		$cas_port = 443;
		
		// Enable debugging
		phpCAS::setDebug();
		
		// Initialize phpCAS
		phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
		
		// For production use set the CA certificate that is the issuer of the cert
		// on the CAS server and uncomment the line below
		// phpCAS::setCasServerCACert($cas_server_ca_cert_path);
		
		// For quick testing you can disable SSL validation of the CAS server.
		// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
		// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
		phpCAS::setNoCasServerValidation();
		
		// force CAS authentication
		//phpCAS::forceAuthentication();
		
		// at this step, the user has been authenticated by the CAS server
		// and the user's login name can be read with phpCAS::getUser().
		
        if(phpCAS::forceAuthentication()) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, phpCAS::getUser(), array());
	    } 
	    else {
	        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, $this->_errors);
	    }
		

    }
         
    /**
     * Parses the xml response from the CAS server
     *
     * @param string $body      The response body of the CAS validation request
     * 
     * @return false|string     Returns false on failure, CAS user on success.
     */
    protected function getResponseBody($body)
    {
    
        $xml = simplexml_load_string($body, 'SimpleXMLElement', 0, $this->_xmlNameSpace);
        
        if(isset($xml->authenticationSuccess)) {
            return get_object_vars($xml->authenticationSuccess);
        }
        else {
            $this->_errors[] = 'Authentication failed: Server response error';
            foreach($xml->authenticationFailure as $i => $failure) {
                $this->_errors[] = $failure->attributes()->code . ': ' . trim($failure);
            }
        }

        return false;
    }
    
    /**
     * Validate a ticket for a service.
     *
     * @param string $ticket  Ticket to validate given by CAS Server
     * @param string $service URL to the service requesting authentication
     * @uses    Zend_Http_Client
     *
     * @return false|string     Returns false on failure, CAS user on success.
     */
    protected function validateTicket($ticket, $service)
    {
        
        /**
         * @see Zend_Http_Client
         */
        require_once 'Zend/Http/Client.php';
        
        try {

            if (! $this->_clientAdapter instanceof Zend_Http_Client_Adapter_Interface) {
                $this->setClientAdapter();
            }

            $this->setClient();
            
            // Pass parameters ticket and service to client
            $this->_client->setParameterGet($this->getValidationParams($ticket, $service));
        
            // Get the client response
            $response = $this->_client->request();
            
            if($response->getStatus() == 200) {
        
                $result = $this->getResponseBody($response->getBody());
        
                if($result === false) {
                    return false;
                }
                else {
                    return $result;
                }
            }

            return false;
            
        } catch (Exception $e) {
            
            // Set error messages for failure
            $this->_errors[] = 'Authentication failed: Failed to connect to server';
            $this->_errors[] = $e->getMessage();
            
            return false;
        }
    }

    /**
     * Returns a full URL that was requested on current HTTP request.
     *
     * @see Zend_OpenId::selfUrl()
     *
     * @return string
     */
    static public function selfUrl()
    {
        if (isset($_SERVER['SCRIPT_URI'])) {
            return $_SERVER['SCRIPT_URI'];
        }
        $url = '';
        $port = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            if (($pos = strpos($_SERVER['HTTP_HOST'], ':')) === false) {
                if (isset($_SERVER['SERVER_PORT'])) {
                    $port = ':' . $_SERVER['SERVER_PORT'];
                }
                $url = $_SERVER['HTTP_HOST'];
            } else {
                $url = substr($_SERVER['HTTP_HOST'], 0, $pos);
                $port = substr($_SERVER['HTTP_HOST'], $pos);
            }
        } else if (isset($_SERVER['SERVER_NAME'])) {
            $url = $_SERVER['SERVER_NAME'];
            if (isset($_SERVER['SERVER_PORT'])) {
                $port = ':' . $_SERVER['SERVER_PORT'];
            }
        }
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $url = 'https://' . $url;
            if ($port == ':443') {
                $port = '';
            }
        } else {
            $url = 'http://' . $url;
            if ($port == ':80') {
                $port = '';
            }
        }

        $url .= $port;
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $url .= $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $query = strpos($_SERVER['REQUEST_URI'], '?');
            if ($query === false) {
                $url .= $_SERVER['REQUEST_URI'];
            } else {
                $url .= substr($_SERVER['REQUEST_URI'], 0, $query);
            }
        } else if (isset($_SERVER['SCRIPT_URL'])) {
            $url .= $_SERVER['SCRIPT_URL'];
        } else if (isset($_SERVER['REDIRECT_URL'])) {
            $url .= $_SERVER['REDIRECT_URL'];
        } else if (isset($_SERVER['PHP_SELF'])) {
            $url .= $_SERVER['PHP_SELF'];
        } else if (isset($_SERVER['SCRIPT_NAME'])) {
            $url .= $_SERVER['SCRIPT_NAME'];
            if (isset($_SERVER['PATH_INFO'])) {
                $url .= $_SERVER['PATH_INFO'];
            }
        }
        return $url;
    }
}