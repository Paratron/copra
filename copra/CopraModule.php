<?php
/**
 * CopraModule
 * ===========
 * This is the basic class model, you can base your REST classes upon.
 *
 * @author: Christian Engel <hello@wearekiss.com>
 * @version: 2 22.03.12
 */

class CopraModule {
    /**
     * @var Copra This variable will hold a reference to the global copra object.
     */
    var $copra = NULL;

    /**
     * @var Object This variable will hold a reference to the parent object, if there is any.
     */
    var $parent = NULL;
    /**
     * @var
     */
    var $parent_id = NULL;

    /**
     * @var array This request methods are applicable to the object type itself.
     */
    protected $general_request_methods = array('GET');

    /**
     * @var array This request methods are applicable to a specific identified object.
     */
    protected $specific_request_methods = array();

    /**
     * @var array List of class names which can be sub-connected to this object type.
     */
    protected $connections = array();

    /**
     * The params array will always be passed on class creation.
     * METHOD contains the HTTP method of the request to decide which action to take.
     * ID can contain the identifier of an object on which to focus.
     * PARENT may contain a reference to a parent class object. This is an indicator that the current class is a sub-object.
     * COPRA is a reference to the global copra object. You can use this to obtain data from the request body, or fire errors.
     *
     * params:
     * {
     *  copra: [object_reference]
     *  parent: [object_reference]/NULL
     *  parent_id: mixed/undefined
     * }
     * @param $params
     */
    public function __construct($params) {
        $this->copra = $params['copra'];

        if(isset($params['parent'])){
            $this->parent = $params['parent'];
        }

        if(isset($params['parent_id'])){
            $this->parent_id = $params['parent_id'];
        }
    }

    /**
     * This function should return an array of possible sub object types which can make use of this object.
     * This function may be called by the Core class right after instancing a new REST class.
     * @static
     * @return array
     */
    public function connection_supported($connection) {
        return in_array($connection, $this->connections);
    }

    /**
     * This function should return an array of possible request methods when either given an ID, or not.
     * @static
     * @param $with_id default FALSE
     * @return array
     */
    public function request_supported($request, $with_id = FALSE){
        if(!$with_id){
            //This request methods are valid if no specific object ID is given.
            return in_array($request, $this->general_request_methods);
        }
        //This request methods are valid, if a specific object ID is given.
        return in_array($request, $this->specific_request_methods);
    }

// =================================================================================================================
// Request Method based functions
// =================================================================================================================

    /**
     * This method will be called when object(s) are being requested.
     * If $id == NULL, the function should return a list of elements.
     * If $fields != NULL, the function should only return the requested fields of the object(s)
     * If $filter != NULL, the function should only return
     *
     * @param mixed $id
     * @return void
     */
    public function get($id) {
    }

    /**
     * This method should create new objects.
     * @return void
     */
    public function post() {
    }

    /**
     * The put method is used to overwrite data in existing objects.
     * @param $id
     * @return void
     */
    public function put($id) {
    }

    /**
     * The delete function removes an object.
     * Don't forget to call the delete_children function to delegate the delete to the child classes (if there are any).
     * Just call parent::delete($id);
     * @param $id
     * @return void
     */
    public function delete($id) {
        $this->delete_children(get_class($this), $id);
    }

    /**
     * Call this function after you deleted an object.
     * This will iterate over all child object classes and tell them that a parent object has been deleted.
     * @param $id
     * @return
     */
    private function delete_children($object_name, $id){
        $childs = $this->sub_objects();
        if(!count($childs)) return;
        foreach($childs as $child){
            if(!class_exists($child)){
                $this->copra->load_class($child);
                $c = new $child();
                $c->mother_deleted($id);
            }
        }
    }

    /**
     * If the current object class is a child class of another object class, this method gets called
     * after delete() has been called on the mother class.
     * @param $id
     * @return void
     */
    public function mother_deleted($id) {
    }
}
