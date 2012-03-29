COPRA - Chainable, Open PHP REST Architecture
=============================================
We created the Copra framework to have the ability to create Interfaces to our Application Objects through a REST interface in a quick and easy way.

The basic structure - understanding COPRA
-----------------------------------------

Every request to the Copra framework has to be made to the copra.php file.
But since you want to use REST based URLs, a modRewrite rule in your .htaccess file is needed to make Copra work:

    RewriteRule (.*) copra.php/$1 [L,QSA]

In this example, every call  will be mapped to the copra.php - you may want to change this rule to use just a subset or anything else.


###The anatomy of a copra based API

Copra handles your manipulatable data as objects, which are explicitely separated from each other. Copra allows you to quickly set up the common CRUD methods to read and manipulate your data - with no limitation to a common DBMS. Whether you want to use the classic MySQL, or MongoDB, or even plain files on your harddrive - there is no limitation in the use of Copra.

###The URL scheme

Each data object is represented by a PHP class which gets only loaded when necessary.
Copra looks at the calling URL to determine which class(es) are needed to be loaded.

Lets have a look how Copra REST URLs are shaped:

    http://api.example.com/[object](/[id])(/[sub-object](/[sub-id))[...].json

A Copra REST URL always consists of the following elements:

####URL Base
    **//api.example.com/**[object](/[id])(/[sub-object](/[sub-id))[...].json
This is the base path of your REST API and should be mapped against the copra.php through your .htaccess file.

####Object type
    //api.example.com/**[object]**(/[id])(/[sub-object](/[sub-id))[...].json
This is required. Copra will go ahead and look if there is a class with a fitting name existent in the configured classes folder.
If so, the class will be loaded and all necessary request information are passed to the class constructor.

####Object ID
    //api.example.com/[object]**(/[id])**(/[sub-object](/[sub-id))[...].json
This is optional. If you want to refer to a specific object, you have to set this URL part.
This moves the object class’ scope from global context to local (focused on a single element).

####Sub Object Type
    //api.example.com/[object](/[id])**(/[sub-object]**(/[sub-id))[...].json
Your data objects can contain logical sub-objects which need a specific scope when called. For example you have blogposts stored in categories - then your sub object type will be the blogpost, while the object-type is the category.
At first, the parent object class will be instantiated, gets passed all the request parameters.
After that, the sub object gets called, passed all request parameters as well, together with reference to the parent object.
Notice: You can stack as many sub objects as you want.

####Sub Object ID
    //api.example.com/[object](/[id])(/[sub-object]**(/[sub-id)**)[...].json
The same as object id, but for the corresponding sub object.

####Data format
    //api.example.com/[object](/[id])(/[sub-object](/[sub-id))[...]**.json**    
This is optional (defaults to JSON). Wheter you add the extension “.json” or “.xml” to the API call, the data returned by the PHP classes will be rendered within the specified template.
You can assign new extensions to different templates (for example for RSS or even HTML) to extend Copra for your needs.


###The PHP Object Classes
We were already informed that Copra uses PHP classes to represent data objects.