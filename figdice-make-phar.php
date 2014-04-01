<?php
$versionFile = file(dirname(__FILE__).'/VERSION.txt');
$version = trim(strstr($versionFile[0], ' '));

$alias = 'figdice-'.$version.'.phar';
$pharfile = 'figdice-'.$version.'.phar';
$srcdir = realpath(dirname(__FILE__).'/src');



class MyRecursiveFilterIterator extends RecursiveFilterIterator {

    public static $FILTERS = array(
	'..',
        '.svn',
    );

    public function accept() {
        return !in_array(
            $this->current()->getFilename(),
            self::$FILTERS,
            true
        );
    }

}

$dirItr    = new RecursiveDirectoryIterator($srcdir);
$filterItr = new MyRecursiveFilterIterator($dirItr);
$itr       = new RecursiveIteratorIterator($filterItr, RecursiveIteratorIterator::SELF_FIRST);



$phar = new Phar($pharfile, 0, $alias);
// add all files in the project

$phar->buildFromIterator($itr, $srcdir);

$stub = <<<'END'
<?php
spl_autoload_register( function($class) {
	if (substr($class, 0, 8) == 'figdice\\') {
		require_once 'phar://' . __FILE__ . '/figdice/' . str_replace('\\', '/', substr($class, 8)) . '.php';
	}
});
__HALT_COMPILER();
END;

$phar->setStub($stub);
$phar->compressFiles(Phar::GZ);

