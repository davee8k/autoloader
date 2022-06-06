<?php
namespace Autoloader;
/**
 * Simple autoloader PHP 5.3+
 * usage: new Autoloader("/path/to/temp.json", ["./phplibs/"=>true], ["./phplibs/dontindex/"=>true]);
 * or: new Autloloader(null, ["./phplibs/"=>false]);
 *
 * @author DaVee
 * @version 0.83.1
 * @license https://unlicense.org/
 */
class Autoloader {
	/** @var string */
	public static $extRegex = '/\.(php|php5)$/i';
	/** @var string[] */
	public static $ignoreItems = array('.','..');
	/** @var bool */
	public static $ignoreDuplicates = true;
	/** @var bool */
	public static $onlyNamespace = false;
	/** @var string */
	public static $markDir = '0 DIR';

	/** @var string|null */
	protected $tempFile;
	/** @var int */
	protected $timeMark = 0;
	/** @var array */
	protected $dirs = array();
	/** @var array */
	protected $ignore = array();
	/** @var array */
	protected $classList = array();

	/**
	 * Create class index and register load function
	 * @param string $tempFile	file for class list cache (can be disabled with null)
	 * @param array $dirs		directories to index
	 * @param array $ignore		directories to skip
	 */
	public function __construct ($tempFile, array $dirs, array $ignore = array()) {
		$this->tempFile = $tempFile;
		$this->dirs = $dirs;
		$this->ignore = $ignore;

		if ($tempFile && is_file($tempFile)) {
			$this->classList = json_decode(file_get_contents($tempFile), true);
			$this->timeMark = filemtime($tempFile);
			$this->checkPath();
		}
		spl_autoload_register(array($this, 'load'));
	}

	/**
	 * Load file with required class
	 * @param string $class			class name
	 * @return null
	 */
	public function load ($class) {
		if (isset($this->classList[$class]) && is_file($this->classList[$class])) {
			require_once $this->classList[$class];
			if (class_exists($class, false) || interface_exists($class, false)
					|| is_callable('trait_exists', true) && (trait_exists($class, false))) return;
		}
		if (empty($this->classList) || $this->isTryReindex($class)) {
			$this->reindex(empty($this->classList) || $this->isTryReindex());
			if (isset($this->classList[$class])) $this->load($class);
		}
	}

	/**
	 * Check if files were indexed in current directory
	 */
	protected function checkPath () {
		if (isset($this->classList[self::$markDir])) {
			if ($this->classList[self::$markDir] == __DIR__) {
				unset($this->classList[self::$markDir]);
			}
			else {
				$this->reindex($this->isTryReindex());
			}
		}
	}

	/**
	 * Timeout for reindexing
	 * @param string $class		class name
	 * @return bool
	 */
	protected function isTryReindex ($class = null) {
		return $this->timeMark < (time() - 10) && (!$class || !self::$onlyNamespace || strpos('\\', $class));
	}

	/**
	 * Search in files for classes and optionally creates temp file with array
	 * @param bool $replace
 	 * @throws \RuntimeException		directory is set instead of settings
	 */
	protected function reindex ($replace = true) {
		$this->classList = array();
		foreach ($this->dirs as $dir=>$search) {
			if (is_string($search)) throw new \RuntimeException("Invalid index setting: ".$search, 500);
			$this->findFiles($dir, $search);
		}
		if ($replace && $this->tempFile) {
			file_put_contents($this->tempFile, json_encode(array(self::$markDir=>__DIR__) + $this->classList));
		}
		$this->timeMark = time();
	}

	/**
	 * Search for source files
	 * @param string $dir
	 * @param bool $searchSubDir
	 */
	protected function findFiles ($dir, $searchSubDir) {
		if (isset($this->ignore[$dir])) {
			if ($this->ignore[$dir]) return;
			$searchSubDir = false;
		}

		$dh = opendir($dir);
		if ($dh) {
			while (false !== ($name = readdir($dh))) {
				if (!in_array($name, self::$ignoreItems)) {
					if ($searchSubDir && is_dir($dir.$name)) {
						$this->findFiles($dir.$name.'/', $searchSubDir);
					}
					else if (preg_match(self::$extRegex, $name)) {
						$this->loadFileClasses($dir.$name, self::$ignoreDuplicates);
					}
				}
			}
			closedir($dh);
		}
	}

	/**
	 * Put found classes into array and check for duplicity
	 * @param string $file
	 * @param bool $ignore		iqnore duplicate classes
	 * @throws \RuntimeException		duplicate exists
	 */
	protected function loadFileClasses ($file, $ignore = false) {
		$source = file_get_contents($file);
		$classes = $this->getContentClasses($source);
		if (!empty($classes)) {
			foreach ($classes as $info) {
				$mark = ($info['NAMESPACE'] ? $info['NAMESPACE'].'\\' : '').$info['CLASS'];
				if (!$ignore && isset($this->classList[$mark])) throw new \RuntimeException('Class: '.$mark.' already defined.', 500);
				$this->classList[$mark] = $file;
			}
		}
	}

	/**
	 * Find classes in source file
	 * @param string $src		source code
	 * @return array
	 */
	protected function getContentClasses ($src) {
		$namespace = '';
		$classes = array();

		$tokens = token_get_all($src);
		$count = count($tokens);

		for ($i = 2; $i < $count; $i++) {
			$objType = $tokens[$i - 2][0];
			if ($objType == T_NAMESPACE && $tokens[$i - 1][0] == (T_WHITESPACE && $tokens[$i][0] == T_STRING || defined('T_NAME_QUALIFIED') && $tokens[$i][0] == T_NAME_QUALIFIED)) {
				$namespace = $tokens[$i][1];
				$j = 2;
				while ($tokens[$i + $j - 1][0] == T_NS_SEPARATOR && $tokens[$i + $j][0] == T_STRING) {
					$namespace .= '\\'.$tokens[$i + $j][1];
					$j += 2;
				}
			}
			else if ( ($objType == T_CLASS || $objType == T_INTERFACE || defined('T_TRAIT') && $objType == T_TRAIT)
						&& $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
				$classes[] = array('NAMESPACE'=>$namespace, 'CLASS'=>$tokens[$i][1]);
			}
		}
		return $classes;
	}
}