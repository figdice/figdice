Starting to work with FigDice in your project is simple.

**Installation**
================

Choose among the 3 methods below, which suits better your needs: Composer, Phar, or Zip file.

**1. Composer**
---------------
Simply add the following composer.json file to your project root, or append the "require" section to your existing composer.json file:

    {
      "require": {
        "figdice/figdice": "*"
      }
    }

Then run the following command in your project folder:

    php composer.phar install

The **\figdice** namespace will automatically become available thanks to the **autoload** Composer feature.



**2. Phar**
-----------
Download the latest FigDice phar file to the location of your choice.
Then, in your source files where you need to use FigDice features, write the line:

    require_once 'phar:///path/to/figdice.phar';

The phar file's stub registers an **autoload** function for the classes in the **\figdice** namespace. Notice that, if you already have an old-style **__autoload** function, you must register it with [spl_autoload_register](http://php.net/manual/en/function.spl-autoload-register.php) before importing the phar.


**3. Zip**
----------
Download the latest Figdice zip file to the location of your choice and extract the archive. Then you have two options:

***3.1 Manual includes ***

In the your source files where you need to use FigDice features, include the necessary files:

    require_once '/path/to/figdice/View.php';
    require_once '/path/to/figdice/Feed.php';
    require_once '/path/to/figdice/Filter.php';

at the minimum.
Notice that some Exceptions and helper classes might need *require* despite you don't use them explicitly in your code.

***3.2 Autoload ***

Simply *require_once* the **autoload.php** file at the root of the FigDice folder.


**Getting Started**
===================

Instanciate a Fig View:

    $figView = new \figdice\View();

Register your Feed provider:

    $feedFactory = new MyFeedFactory();
    $figView->registerFeedFactory( $feedFactory );

And load and render your XML template:

    $figView->loadFile( 'my-template.xml' );
    echo $figView->render();


The Feed Factory is the class you define, which will provide an instance of the Feeds that your template pulls with instruction:

    <fig:feed class="\your\FeedClass" target="mountPoint" />

It is responsible for identifying which file needs loading, and for creating a new instance of the **\figdice\Feed** derived class of yours, which handles the data gathering as per the template's request.

See [Documentation](http://www.figdice.org/en/manual.html) for more details.




