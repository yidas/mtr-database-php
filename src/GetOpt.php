<?php

/**
 * GetOpt
 * 
 * Base on getopt(), provide option handler with cached mechanism for get and check option values.
 * 
 * @author  Nick Tsai <myintaer@gmail.com>
 * @version 1.0.0
 * @see     http://php.net/manual/en/function.getopt.php#refsect1-function.getopt-parameters
 * @param   string options
 * @param   array longopts
 * @param   int optind
 * @example
 *  $getOpt = new GetOpt('h:v', ['host:', 'verbose']);
 *  $hostname = $getOpt->get(['project', 'p']);     // String or null
 *  $debugOn = $getOpt->has(['verbose', 'v']);      // Bool
 */
class GetOpt
{
    /**
     * @var array Cached options
     */
    private $_options;
    
    function __construct($options, array $longopts=[], $optind=null) 
    {
        // $optind for PHP 7.1.0
        $this->_options = ($optind) 
            ? getopt($options, $longopts, $optind)
            : getopt($options, $longopts);
        
        return $this;
    }

    /**
     * Get Option Value
     * 
     * @param string|array Option priority key(s) for same purpose
     * @return mixed Result of purpose option value by getopt(), return null while not set
     * @example
     *  $verbose = $this->get(['verbose', 'v']);
     */
    public function get($options)
    {
        // String Key
        if (is_string($options)) {
            
            return (isset($this->_options[$options])) ? $this->_options[$options] : null;
        }
        // Array Keys
        if (is_array($options)) {
            // Mapping loop
            foreach ($options as $key => $option) {
                // First match
                if (isset($this->_options[$option])) {

                    return $this->_options[$option];
                }
            }
        }

        return null;
    }

    /**
     * Get Option Value
     * 
     * @param string|array Option priority key(s) for same purpose
     * @return mixed Result of purpose option value by getopt(), return null while not set
     * @example
     *  $verbose = $this->get(['verbose', 'v']);
     */
    public function has($options)
    {
        // String Key
        if (is_string($options)) {
            
            return (isset($this->_options[$options])) ? true : false;
        }
        // Array Keys
        if (is_array($options)) {
            // Mapping loop
            foreach ($options as $key => $option) {
                // First match
                if (isset($this->_options[$option])) {

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get Options
     * 
     * @return array $this->$_options
     */
    public function getOptions()
    {
        return $this->_options;
    }
}
