<?php
use Autoloader\Autoloader;

class AutoloaderTest extends \PHPUnit\Framework\TestCase {

	protected function callPrivateProperty ($obj, $name) {
		$class = new \ReflectionClass($obj);
		return $class->getProperty($name)->getValue($obj);
	}

	public function test_indexAll_success () {
		$classList = [
				'NamespaceClass\NamespaceClass' => './SourcesToIndex/NamespaceClass.php',
				'DuplicityClass' => './SourcesToIndex/Duplicates/DuplicityClass.php',
				'TestClass' => './SourcesToIndex/TestClass.php',
				'TraitClass' => './SourcesToIndex/TraitClass.php'
			];

		$loader = new Autoloader(null, ['./SourcesToIndex/'=>true]);
		$loader->load('TestClass');

		$this->assertEquals($this->callPrivateProperty($loader, 'classList'), $classList);
	}

	public function test_indexSingleDir_success () {
		$classList = [
				'NamespaceClass\NamespaceClass' => './SourcesToIndex/NamespaceClass.php',
				'TestClass' => './SourcesToIndex/TestClass.php',
				'TraitClass' => './SourcesToIndex/TraitClass.php'
			];

		$loader = new Autoloader(null, ['./SourcesToIndex/'=>false]);
		$loader->load('TestClass');

		$this->assertEquals($this->callPrivateProperty($loader, 'classList'), $classList);
	}

	public function test_indexIgnore_success () {
		$classList = [
				'NamespaceClass\NamespaceClass' => './SourcesToIndex/NamespaceClass.php',
				'TestClass' => './SourcesToIndex/TestClass.php',
				'TraitClass' => './SourcesToIndex/TraitClass.php'
			];

		$loader = new Autoloader(null, ['./SourcesToIndex/'=>true], ['./SourcesToIndex/Duplicates/'=>true]);
		$loader->load('TestClass');

		$this->assertEquals($this->callPrivateProperty($loader, 'classList'), $classList);
	}

	public function test_indexDuplicate_fail () {
		Autoloader::$ignoreDuplicates = false;
		$loader = new Autoloader(null, ['./SourcesToIndex/'=>true]);

		$this->expectException('RuntimeException', 'Class: DuplicityClass already defined.');

		$loader->load('TestClass');
	}
}