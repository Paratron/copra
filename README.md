COPRA - Chainable, Open PHP REST Architecture
=============================================
We created the Copra framework to have the ability to create Interfaces to our Application Objects through a REST interface in a quick and easy way.

The basic structure - understanding COPRA
-----------------------------------------

Every request to the Copra framework has to be made to the copra.php file.
But since you want to use REST based URLs, a modRewrite rule in your .htaccess file is needed to make Copra work:

    RewriteRule (.*) api.php/$1 [L,QSA]

In this example, every call  will be mapped to the copra.php - you may want to change this rule to use just a subset or anything else.

In your api.php file, you need to set up these simple lines to make Copra work:

    require('copra/Copra.php');
    $c = new Copra(array(
                        'classes_folder' => 'classes/'
                   ));
    $c->go();

The "classes_folder" setting tells Copra where it can find the folder with the classes to match calls against.

###The anatomy of a copra based API

Copra handles your manipulatable data as objects, which are explicitely separated from each other. Copra allows you to quickly set up the common CRUD methods to read and manipulate your data - with no limitation to a common DBMS. Whether you want to use the classic MySQL, or MongoDB, or even plain files on your harddrive - there is no limitation in the use of Copra.

###The URL scheme

Each data object is represented by a PHP class which gets only loaded when necessary.
Copra looks at the calling URL to determine which class(es) are needed to be loaded.

Lets have a look how Copra REST URLs are shaped:

    http://api.example.com/[object](/[id])(/[sub-object](/[sub-id))[...].json

A Copra REST URL always consists of the following elements:

####URL Base
This is the base path of your REST API and should be mapped against the copra.php through your .htaccess file.

####Object type
This is required. Copra will go ahead and look if there is a class with a fitting name existent in the configured classes folder.
If so, the class will be loaded and all necessary request information are passed to the class constructor.

####Object ID
This is optional. If you want to refer to a specific object, you have to set this URL part.
This moves the object class’ scope from global context to local (focused on a single element).

####Sub Object Type
Your data objects can contain logical sub-objects which need a specific scope when called. For example you have blogposts stored in categories - then your sub object type will be the blogpost, while the object-type is the category.
At first, the parent object class will be instantiated, gets passed all the request parameters.
After that, the sub object gets called, passed all request parameters as well, together with reference to the parent object.
Notice: You can stack as many sub objects as you want.

####Sub Object ID
The same as object id, but for the corresponding sub object.

####Data format
This is optional (defaults to JSON). Wheter you add the extension “.json” or “.xml” to the API call, the data returned by the PHP classes will be rendered within the specified template.
You can assign new extensions to different templates (for example for RSS or even HTML) to extend Copra for your needs.


The PHP Object Classes
----------------------
We were already informed that Copra uses PHP classes to represent data objects.
If you have set up copra in the same way we showed it in the example above, you have to put your REST classes inside the "classes/" folder.
Lets create an example class:

    class basic_object extends CopraModule{
        function get($id){
            return array('hello' => 'world');
        }
    }

Thats it. You can call the class now performing a GET request to this URL:

    [API_BASE]/basic_object

Which will return you this JSON string:

    {"success":{"hello":"world"}}

Simply create a class method for every HTTP request method you want to fetch.
The basic ones are POST, GET, PUT and DELETE.

###Fetching the request body
Fetching the request body for GET and POST requests can be done through PHPs normal magic variables $_GET and $_POST, altough this is not possible for PUT and DELETE requests (or other kinds of request).
We recommend you fetching all request data that is transfered in the request body (thats the case for all requests, except GET), with the function

    $this->copra->request_data();

inside your class. This will give you the request body, already interpreted as an associative array, like you know it from $_GET or $_POST.

###Initializing your class
To initialize your class and run any code before one of the request function is called, simply define a function "init()" inside your module class.

You COULD create a __construct() function, but then you need to call the constructor of the parent class to keep everything in the works, so its simply easier to create an "init()" function for you.

####Making variables and objects publicly available inside the Copra architecture
Sometimes you need a variable or object at hand in any of the dynamic classes - for example you create a database object to interact with your database.

At any point after the creation of the Copra object, you can call the function "make_public($name, $var)" of the Copra class.
This makes a reference of the variable/object available unter $copa->public->\[name\]

In the case of a database object, you should make this public BEFORE you call the go() function of the copra object.

You can call the make_public() function at any time inside your class constructs, by calling $this->copra->make_public();
This is especially handy if you want to fetch userdata in the AuthClass' validate_token() function (see below) and make it publicly accessable in the whole object structure.

##Authenticating users
User authentication is already build into Copra and is handled in the class "CopraAuth".
The class has two pre-defined functions, which you have to change to your needs before you are able to authenticating users.

###login()
The login() function is called, when the user makes a POST request to the API root.
The example function in the CopraAuth.php requires the two parameters "$user" and "$password", but that can be changed to whatever parameter you would like to have submitted.

Copra automatically checks which parameters your login() function awaits and if they are submitted inside the POST data.    
If so, the login function gets called and passed all the necessary parameters.

So if you rather want to use the parameters "$email" and "$password", just go for it.
Or add a third parameter "$api_key" or whatever.

Copra expects the function to return an array \["token" => "\[your access token\]"\].

###validate_token()
This function is called, whenever the GET parameter "token" is attached to the URL.
It gets passed the access token for validation.
Return an array with strings of permissions the user gets for using this token.

Copra caches this permissions array and you can easily test for a specific permission by calling

    $this->copra->has_permission($permission);

from within your module classes, which will return you a true or false.