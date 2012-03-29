<?php
/**
 * Copra Core Class
 * ================
 * This class handles the requests, loading of classes and rendering in templates.
 * It also will be used for auth requests and gets passed to the sub classes.
 *
 * @author: Christian Engel <hello@wearekiss.com> 
 * @version: 1 11.03.2012
 */

class Copra {
    private $request_data_cache = NULL;
    private $classes_folder = '';
    private $default_template = 'json';

    /**
     * The Copra Class Constructor.
     * @param array $params
     */
    function __construct($params = array()){
        require('copra/CopraModule.php');
        require('copra/CopraAuth.php');

        if(isset($params['classes_folder'])){
            $this->classes_folder = $params['classes_folder'];
        }

        if(isset($params['default_template'])){
            $this->default_template = $params['default_template'];
        }
    }

    /**
     * This starts the Copra app.
     * @return void
     */
    function go(){
        $path = $_SERVER['PATH_INFO'];
        $path_elements = explode('/', $path);
        $len = count($path_elements);

        $classes = array();

        for($i = 0; $i < $len;$i+=2){
            if(!$this->load_class($path_elements[$i])){
                return $this->error('Unknown path components', '/'.$path_elements[$i]);
            }

            $id = NULL;
            if($i+1 < $len) $id = $path_elements[$i+1];
        }
    }

    /**
     * Loads a class if its not existent.
     * @param $classname
     * @return boolean
     */
    function load_class($classname){
        $path = $this->classes_folder.'/'.basename($classname).'php';
        if(!file_exists($path)) return FALSE;
        if(!class_exists($classname)){
            require($path);
        }
        return TRUE;
    }


    /**
     * Dependent on the HTTP request method, data is not always accessable via $_POST.
     * This function will return the data which was passed in the request body, even when a DELETE or PUT request was made.
     * @return void
     */
    function get_request_data(){
        if($this->request_data_cache === NULL){
            $data = array();
            parse_str(file_get_contents('php://input'), $data);
            $this->request_data_cache = $data;
        }

        return $this->request_data_cache;
    }

    /**
     * This will create a error message and send it to the user.
     * A proper HTTP statuscode will be sent as well (by default: 403).
     * Here are a couple of statuscodes that can be used with any API:
     * 401 - unauthorized
     * 403 - forbidden
     * 404 - not found
     * 405 - method not allowed
     *
     * This method automatically
     * @param $message
     * @param null $data
     * @return FALSE
     */
    function error($message, $data = NULL, $status = 403){
        $statuses = array(
            401 => 'Unauthorized',
            401 => 'Forbidden',
            404 => 'Not found',
            405 => 'Method not allowed'
        );

        $err = array(
            'error' => array(
                'message' => $message
            )
        );

        if($data !== NULL) $err['error']['data'] = $data;

        if(isset($statuses[$status])) $status .= ' '.$statuses[$status];
        header('HTTP/1.1 '.$status);
        $this->render($err);
        return FALSE;
    }

    /**
     * Will render any data through a template.
     * The default and fallback template is "json".
     * @param $data
     * @param string $template
     * @return void
     */
    function render($data, $template = ''){
        if(!$template) $template = $this->default_template;
        
        $tpl = basename($template);
        $gzip = (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) ? TRUE : FALSE;
        if(!file_exists('copra/templates/'.$tpl.'.php')) $tpl = 'json';

        if($gzip) ob_start('ob_gzhandler');

        include('copra/templates/'.$tpl);
    }
}
