<?php
use Autoloader\Autoloader;

class AutoloaderTest extends \PHPUnit\Framework\TestCase {

	protected function callPrivateProperty ($obj, $name) {
		$class = new \ReflectionClass($obj);
		return $class->getProperty($name)->getValue($obj);
	}

	public function test_indexAll_success () {
		$classList = [
			    'space\Ignore' => './SourcesToIndex/Core/Subdir/Ignore/multipleClasses.php',
				'space\sub\s_b\AnotherClass' => './SourcesToIndex/Core/Subdir/Ignore/multipleClasses.php',
				'DuplicityClass' => './SourcesToIndex/Core/Subdir/DuplicityClass.php',
				'NamespaceClass\Subdir\NamespaceClass' => './SourcesToIndex/Core/Subdir/NamespaceClass.php',
				'EnumClass' => './SourcesToIndex/Core/EnumClass.php',
				'NamespaceClass\NamespaceClass' => './SourcesToIndex/Core/NamespaceClass.php',
				'TestClass' => './SourcesToIndex/Core/TestClass.php',
				'TraitClass' => './SourcesToIndex/Core/TraitClass.php'
			];

		$loader = new Autoloader(null, ['./SourcesToIndex/Core/'=>true]);
		$loader->load('TestClass');

		$this->assertEquals($this->callPrivateProperty($loader, 'classList'), $classList);
	}

	public function test_indexSingleDir_success () {
		$classList = [
				'EnumClass' => './SourcesToIndex/Core/EnumClass.php',
				'NamespaceClass\NamespaceClass' => './SourcesToIndex/Core/NamespaceClass.php',
				'TestClass' => './SourcesToIndex/Core/TestClass.php',
				'TraitClass' => './SourcesToIndex/Core/TraitClass.php'
			];

		$loader = new Autoloader(null, ['./SourcesToIndex/Core/'=>false]);
		$loader->load('TestClass');

		$this->assertEquals($this->callPrivateProperty($loader, 'classList'), $classList);
	}

	public function test_indexIgnoreAll_success () {
		$classList = [
				'EnumClass' => './SourcesToIndex/Core/EnumClass.php',
				'NamespaceClass\NamespaceClass' => './SourcesToIndex/Core/NamespaceClass.php',
				'TestClass' => './SourcesToIndex/Core/TestClass.php',
				'TraitClass' => './SourcesToIndex/Core/TraitClass.php'
			];

		$loader = new Autoloader(null, ['./SourcesToIndex/Core/'=>true], ['./SourcesToIndex/Core/Subdir/'=>true]);
		$loader->load('TestClass');

		$this->assertEquals($this->callPrivateProperty($loader, 'classList'), $classList);
	}

	public function test_indexIgnoreSub_success () {
		$classList = [
				'DuplicityClass' => './SourcesToIndex/Core/Subdir/DuplicityClass.php',
				'NamespaceClass\Subdir\NamespaceClass' => './SourcesToIndex/Core/Subdir/NamespaceClass.php',
				'EnumClass' => './SourcesToIndex/Core/EnumClass.php',
				'NamespaceClass\NamespaceClass' => './SourcesToIndex/Core/NamespaceClass.php',
				'TestClass' => './SourcesToIndex/Core/TestClass.php',
				'TraitClass' => './SourcesToIndex/Core/TraitClass.php'
			];

		$loader = new Autoloader(null, ['./SourcesToIndex/Core/'=>true], ['./SourcesToIndex/Core/Subdir/'=>false]);
		$loader->load('TestClass');

		$this->assertEquals($this->callPrivateProperty($loader, 'classList'), $classList);
	}

	public function test_indexDuplicates_success () {
		Autoloader::$ignoreDuplicates = true;
		$classList = [
			    'space\Ignore' => './SourcesToIndex/Core/Subdir/Ignore/multipleClasses.php',
				'space\sub\s_b\AnotherClass' => './SourcesToIndex/Core/Subdir/Ignore/multipleClasses.php',
				'EnumClass' => './SourcesToIndex/Core/EnumClass.php',
				'NamespaceClass\NamespaceClass' => './SourcesToIndex/Core/NamespaceClass.php',
				'TestClass' => './SourcesToIndex/Core/TestClass.php',
				'TraitClass' => './SourcesToIndex/Core/TraitClass.php',
				'NamespaceClass\Subdir\NamespaceClass' => './SourcesToIndex/Core/Subdir/NamespaceClass.php',
				'DuplicityClass' => './SourcesToIndex/Duplicates/DuplicityClassToo.php'
			];

		$loader = new Autoloader(null, ['./SourcesToIndex/Core/'=>true, './SourcesToIndex/Duplicates/'=>true]);
		$loader->load('TestClass');

		Autoloader::$ignoreDuplicates = false;
		$this->assertEquals($this->callPrivateProperty($loader, 'classList'), $classList);
	}

	public function test_indexDuplicates_fail () {
		$loader = new Autoloader(null, ['./SourcesToIndex/Core/'=>true, './SourcesToIndex/Duplicates/'=>true]);

		$this->expectException('RuntimeException', 'Class: DuplicityClass already defined.');

		$loader->load('TestClass');
	}
}