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

class CopraPublic{} //Used to create a empty Object.

class Copra {
    private $request_data_cache = NULL;
    private $classes_folder = '';
    private $default_template = 'json';

    private $permissions = array();
    private $access_token = NULL;
    private $auth = NULL;

    var $request_method = '';
    var $public = NULL;

    /**
     * The Copra Class Constructor.
     * @param array $params
     */
    function __construct($params = array()) {
        $this->public = new CopraPublic();

        require('copra/CopraModule.php');
        require('copra/CopraAuth.php');

        $this->auth = new CopraAuth();

        if (isset($params['classes_folder'])) {
            $this->classes_folder = $params['classes_folder'];
            if (substr($params['classes_folder'], -1) != '/') $this->classes_folder .= '/';
        }

        if (isset($params['default_template'])) {
            $this->default_template = $params['default_template'];
        }
    }

    /**
     * Makes a variable publicly available through $copra->public->[objectname]
     * Be careful: if the object is already defined, it gets replaced.
     * @param string $object_name
     * @param mixed $object
     * @return void
     */
    function make_public($object_name, $object){
        $this->public->$object_name =& $object;
    }

    /**
     * This starts the Copra app.
     * @return void
     */
    function go() {
        $this->request_method = $_SERVER['REQUEST_METHOD'];

        if(!isset($_SERVER['PATH_INFO'])){
            if($this->request_method == 'POST'){
                $reflect = new ReflectionMethod('CopraAuth', 'login');
                $p = $reflect->getParameters();
                $params = array();
                foreach($p as $v){
                    if(!isset($_POST[$v->name])){
                        return $this->error('Missing login parameter', $v->name);
                    }
                    $params[$v->name] = $_POST[$v->name];
                }
                
                $result = call_user_func_array(array($this->auth, 'login'), $params);
                $this->render($result);
                die();
            }
        }

        if(isset($_GET['token'])){
            $this->access_token = $_GET['token'];
            $this->permissions = $this->auth->validate_token($_GET['token']);
        }

        $path = $_SERVER['PATH_INFO'];
        if (substr($path, 0, 1) == '/') $path = substr($path, 1);
        $path_elements = explode('/', $path);
        $len = count($path_elements);

        $classes = array();
        $last = FALSE;
        $last_id = 0;

        for ($i = 0; $i < $len; $i += 2) {

            if($last !== FALSE){
                //Okay, there has already been a class instanced, so this should be a child class.
                //Does the parent class support this child connection?
                if(!$classes[$last]->connection_supported($path_elements[$i])){
                    return $this->error('Unsupported connection', $path_elements[$i], 404);
                }
            }

            if (!$this->load_class($path_elements[$i])) {
                return $this->path_error($path_elements[$i]);
            }

            $id = NULL;
            if ($i + 1 < $len) $id = $path_elements[$i + 1];
            if(!$id) $id = NULL;

            $params = array(
                'copra' => &$this
            );
            if($last !== FALSE){
                $params['parent'] = &$classes[$last];
                $params['parent_id'] = $last_id;
            }
            $classes[] = new $path_elements[$i]($params);

            $last = count($classes)-1;
            $last_id = $id;

            if ($i == $len - 2 || $i == $len - 1) {
                if(!$classes[$last]->request_supported($this->request_method, $id)){
                    return $this->error('Unknown request method', $this->request_method, 405);
                }

                if(method_exists($classes[$last], 'init')){
                    call_user_func(array($classes[$last], 'init'));
                }

                $result = call_user_func(array($classes[$last], strtolower($this->request_method)), $id);

                if($result){
                    $this->render(array('success' => $result));
                }
                
            }

        }
    }

    /**
     * Checks, if the currently used access token has a specific permission.
     * @param string $permission
     * @return bool
     */
    function has_permission($permission){
        if($this->access_token == NULL){
            $this->error('No access token provided');
            die();
        }
        return in_array($permission, $this->permissions);
    }

    /**
     * Loads a class if its not existent.
     * @param $classname
     * @return boolean
     */
    function load_class($classname) {
        $path = $this->classes_folder . basename($classname) . '.php';
        if (!file_exists($path)) return FALSE;
        if (!class_exists($classname)) {
            require($path);
        }
        return TRUE;
    }


    /**
     * Dependent on the HTTP request method, data is not always accessable via $_POST.
     * This function will return the data which was passed in the request body, even when a DELETE or PUT request was made.
     * @return void
     */
    function get_request_data() {
        if ($this->request_data_cache === NULL) {
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
    function error($message, $data = NULL, $status = 403) {
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

        if ($data !== NULL) $err['error']['data'] = $data;

        if (isset($statuses[$status])) $status .= ' ' . $statuses[$status];
        header('HTTP/1.1 ' . $status);
        $this->render($err);
        return FALSE;
    }

    function path_error($path = ''){
        if($path) $path = '/' . $path;
        return $this->error('Unknown path components', $path, 404);
    }

    /**
     * Will render any data through a template.
     * The default and fallback template is "json".
     * @param $data
     * @param string $template
     * @return void
     */
    function render($data, $template = '') {
        if (!$template) $template = $this->default_template;

        $tpl = basename($template);
        $gzip = (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) ? TRUE : FALSE;
        if (!file_exists('copra/templates/' . $tpl . '.php')) $tpl = 'json';

        if ($gzip) ob_start('ob_gzhandler');

        include('copra/templates/' . $tpl . '.php');
    }
}
