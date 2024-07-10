<?php declare(strict_types=1);

namespace Autoloader;

use RuntimeException;

/**
 * Simple autoloader PHP 7.1+
 *
 * basic usage:
 * <code>
 * new Autoloader("/path/to/temp.json", ["./directory/"=>true], ["./directory/dont-index/"=>true]);
 * or:
 * new Autoloader(null, ["./directory/"=>false]);
 * </code>
 *
 * @author DaVee8k
 * @license https://unlicense.org/
 * @version 0.87.1
 */
class Autoloader {
	/** @var string		Index files with extensions */
	public static $extRegex = '/\.(php|php5)$/i';
	/** @var string[]	Ignore files when indexing */
	public static $ignoreItems = ['.','..'];
	/** @var bool		Ignore duplicate classes */
	public static $ignoreDuplicates = false;
	/** @var bool		Allow only classes with namespace */
	public static $onlyNamespace = false;
	/** @var string		Special directory name for testing when script are moved to different directory */
	public static $markDir = '0 DIR';

	/** @var string|null */
	protected $tempFile;
	/** @var int|false */
	protected $timeMark = 0;
	/** @var array<string, bool> */
	protected $dirs = [];
	/** @var array<string, bool> */
	protected $ignore = [];
	/** @var array<string, string> */
	protected $classList = [];

	/**
	 * Create class index and register load function
	 * @param string|null $tempFile	File for class list cache (can be disabled with null)
	 * @param array<string, bool> $dirs		Directories to index
	 * @param array<string, bool> $ignore	directories to skip
	 */
	public function __construct (?string $tempFile, array $dirs, array $ignore = []) {
		$this->tempFile = $tempFile;
		$this->dirs = $dirs;
		$this->ignore = $ignore;

		if ($tempFile && is_file($tempFile)) {
			$source = file_get_contents($tempFile);
			if ($source) $this->classList = json_decode($source, true);
			$this->timeMark = filemtime($tempFile);
			$this->checkPath();
		}
		spl_autoload_register([$this, 'load']);
	}

	/**
	 * Load file with required class
	 * @param string $class			class name
	 */
	public function load (string $class): void {
		if (isset($this->classList[$class]) && is_file($this->classList[$class])) {
			require_once $this->classList[$class];
			if (class_exists($class, false) || interface_exists($class, false) || (trait_exists($class, false))) return;
		}
		if (empty($this->classList) || $this->isTryReindex($class)) {
			$this->reindex(empty($this->classList) || $this->isTryReindex());
			if (isset($this->classList[$class])) $this->load($class);
		}
	}

	/**
	 * Check if files were indexed in current directory
	 */
	protected function checkPath (): void {
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
	 * @param string|null $class	class name
	 * @return bool
	 */
	protected function isTryReindex (string $class = null): bool {
		return $this->timeMark < (time() - 10) && (!$class || !self::$onlyNamespace || strpos('\\', $class));
	}

	/**
	 * Search in files for classes and optionally creates temp file with array
	 * @param bool $replace
	 * @throws RuntimeException		directory is set instead of settings
	 */
	protected function reindex (bool $replace = true): void {
		$this->classList = [];
		foreach ($this->dirs as $dir=>$search) {
			$this->findFiles($dir, $search);
		}
		if ($replace && $this->tempFile) {
			file_put_contents($this->tempFile, json_encode([self::$markDir=>__DIR__] + $this->classList), LOCK_EX);
		}
		$this->timeMark = time();
	}

	/**
	 * Search for source files
	 * @param string $dir
	 * @param bool $searchSubDir
	 */
	protected function findFiles (string $dir, bool $searchSubDir): void {
		if (isset($this->ignore[$dir])) {
			if ($this->ignore[$dir]) return;
			$searchSubDir = false;
		}

		$handle = opendir($dir);
		if ($handle) {
			while (false !== ($name = readdir($handle))) {
				if (!in_array($name, self::$ignoreItems)) {
					if ($searchSubDir && is_dir($dir.$name)) {
						$this->findFiles($dir.$name.'/', $searchSubDir);
					}
					else if (preg_match(self::$extRegex, $name)) {
						$this->loadFileClasses($dir.$name, self::$ignoreDuplicates);
					}
				}
			}
			closedir($handle);
		}
	}

	/**
	 * Put found classes into array and check for duplicity
	 * @param string $file
	 * @param bool $ignore		ignore duplicate classes
	 * @throws RuntimeException		duplicate exists
	 */
	protected function loadFileClasses (string $file, bool $ignore = false): void {
		$source = file_get_contents($file);
		$classes = $source ? $this->getContentClasses($source) : null;
		if (!empty($classes)) {
			foreach ($classes as $info) {
				$mark = ($info['NAMESPACE'] ? $info['NAMESPACE'].'\\' : '').$info['CLASS'];
				if (!$ignore && isset($this->classList[$mark])) throw new RuntimeException('Class: '.$mark.' already defined.', 500);
				$this->classList[$mark] = $file;
			}
		}
	}

	/**
	 * Find classes in source file
	 * @param string $src		source code
	 * @return array<array<string, string>>
	 */
	protected function getContentClasses (string $src): array {
		$namespace = '';
		$classes = [];

		$tokens = token_get_all($src);
		$count = count($tokens);

		for ($i = 2; $i < $count; $i++) {
			$objType = $tokens[$i - 2][0];
			if ($objType == T_NAMESPACE && $tokens[$i - 1][0] == T_WHITESPACE && ($tokens[$i][0] == T_STRING || defined('T_NAME_QUALIFIED') && $tokens[$i][0] == T_NAME_QUALIFIED)) {
				$namespace = $tokens[$i][1];
				$pos = 2;
				while ($tokens[$i + $pos - 1][0] == T_NS_SEPARATOR && $tokens[$i + $pos][0] == T_STRING) {
					$namespace .= '\\'.$tokens[$i + $pos][1];
					$pos += 2;
				}
			}
			else if ( ($objType == T_CLASS || $objType == T_INTERFACE || $objType == T_TRAIT || defined('T_ENUM') && $objType == T_ENUM)
						&& $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
				$classes[] = ['NAMESPACE'=>$namespace, 'CLASS'=>$tokens[$i][1]];
			}
		}
		return $classes;
	}
}
