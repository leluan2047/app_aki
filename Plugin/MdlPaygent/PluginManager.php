<?php
namespace Plugin\MdlPaygent;

use Eccube\Plugin\AbstractPluginManager;

class PluginManager extends AbstractPluginManager
{
	/**
	 * Image folder path (cop source)
	 * @var type
	 */
	protected $imgSrc;

	/**
	 * Image folder path (copy destination)
	 * @var type
	 */
	protected $imgDst;

	public function __construct() {
		$this->imgSrc = __DIR__ . '/Resource/img/';
		$this->imgDst = __DIR__ . '/../../../html/plugin/mdl_pg';
	}

	public function install($config, $app) {
		$this->migrationSchema($app, __DIR__ . '/Migration', $config['code'], 0);
		$this->migrationSchema($app, __DIR__ . '/Migration', $config['code']);

		$this->copyImages($this->imgSrc, $this->imgDst);
	}

	public function uninstall ($config, $app) {
		$this->migrationSchema($app, __DIR__ . '/Migration', $config['code'], 0);
		// Remove images in /html/user_data/packages/default/img/
		$this->removeImages($this->imgDst);
	}

	public function enable ($config, $app) {
		// Update payment status when enable plugin
	}

	public function disable ($config, $app) {
		// Update payment status when enable plugin
	}

	public function update($config, $app) {
		$this->migrationSchema($app, __DIR__ . '/Migration', $config['code']);
	}

	/**
	 * Recursively copy images from $src path to $dst path
	 * @param string $src
	 * @param string $dst
	 */
	protected function copyImages($src, $dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while (false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . '/' . $file)) {
					$this->copyImages($src . '/' . $file, $dst . '/' . $file);
				} else {
					copy($src . '/' . $file, $dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

	/**
	 * Recursively delete images in a folder path
	 * @param string $dir
	 * @return boolean
	 */
	function removeImages($dir) {
		if (!file_exists($dir))
			return true;
		if (!is_dir($dir) || is_link($dir))
			return unlink($dir);
		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..')
				continue;
			if (!$this->removeImages($dir . "/" . $item)) {
				chmod($dir . "/" . $item, 0777);
				if (!$this->removeImages($dir . "/" . $item))
					return false;
			};
		}
		return rmdir($dir);
	}
}
?>