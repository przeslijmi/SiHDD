<?php declare(strict_types=1);

namespace Przeslijmi\SiHDD;

use Przeslijmi\Sexceptions\Exceptions\ClassFopException;
use Przeslijmi\Sexceptions\Exceptions\MethodFopException;
use Przeslijmi\Sexceptions\Exceptions\ParamWrosynException;
use Przeslijmi\Sexceptions\Exceptions\RegexTestFailException;
use Przeslijmi\Sivalidator\GeoProgression;
use Przeslijmi\Sivalidator\RegEx;

/**
 * Object representing dir - used alone or incompound with File.
 */
class Dir extends Path
{

    /**
     * Read list of files - filled by `read()` method - empty earlier; not reused.
     *
     * @var   array
     * @since v2.0
     */
    private $elements;

    /**
     * Constructor.
     *
     * @param string  $fullPath Whole path.
     * @param integer $options  Options transferred ad const (see above).
     *
     * @throws ClassFopException On creationOfPath when creation of path is not possible.
     * @since  v2.0
     */
    public function __construct(string $fullPath, int $options = 0)
    {

        // Create Path.
        try {
            parent::__construct($fullPath, $options);
        } catch (ClassFopException $e) {
            throw (new ClassFopException('creationOfDirectory', $e))->addInfo('fullPath', $fullPath);
        }

        // Check if this is not dir path.
        if ($this->isDir() === false) {
            throw (new ClassFopException('dirPathCannotBeANonDirPath'))->addInfo('fullPath', $fullPath);
        }
    }

    /**
     * Reads and delivers elements (files and dirs) with mask for this Dir.
     *
     * @param null|string $mask Optional. To be applied if given.
     *
     * @since  v2.0
     * @return string[]
     */
    public function read(?string $mask = null) : array
    {

        // Read all elements.
        $this->elements = $this->readRaw();

        // Apply mask if needed.
        if (empty($mask) === false) {
            foreach ($this->elements as $uri => $element) {
                if (fnmatch($mask, $element['uri']) === false) {
                    unset($this->elements[$uri]);
                }
            }
        }

        return array_keys($this->elements);
    }

    /**
     * Count dirs and files found in Dir that meet mask.
     *
     * @param null|string $mask      Optional. To be applied if given.
     * @param boolean     $onlyFiles Optional (false). Set to true to count only files.
     * @param boolean     $onlyDirs  Optional (false). Set to true to count only dirs.
     *
     * @since  v2.0
     * @return integer
     */
    public function count(?string $mask = null, bool $onlyFiles = false, bool $onlyDirs = false) : int
    {

        // Do not renew reading if reading has been already done.
        if ($this->elements === null) {
            $this->read();
        }

        // Short way.
        if (count($this->elements) === 0) {
            return 0;
        }

        // Lvd.
        $localSet = $this->elements;

        // Ignore dirs.
        if ($onlyDirs === true) {
            foreach ($localSet as $uri => $element) {
                if ($element['isDir'] === false) {
                    unset($localSet[$uri]);
                }
            }
        }

        // Ignore files.
        if ($onlyFiles === true) {
            foreach ($localSet as $uri => $element) {
                if ($element['isFile'] === false) {
                    unset($localSet[$uri]);
                }
            }
        }

        // Apply mask if needed.
        if (empty($mask) === false) {
            foreach ($localSet as $uri => $element) {
                if (fnmatch($mask, $element['uri']) === false) {
                    unset($localSet[$uri]);
                }
            }
        }

        return count($localSet);
    }

    /**
     * Count files found in Dir that meet mask.
     *
     * @param null|string $mask Optional. To be applied if given.
     *
     * @since  v2.0
     * @return integer
     */
    public function countFiles(?string $mask = null)
    {

        return $this->count($mask, true);
    }

    /**
     * Count dirs found in Dir that meet mask.
     *
     * @param null|string $mask Optional. To be applied if given.
     *
     * @since  v2.0
     * @return integer
     */
    public function countDirs(?string $mask = null) : int
    {

        return $this->count($mask, false, true);
    }

    /**
     * Add files modification times to every file in set.
     *
     * @since  v2.0
     * @return array
     */
    public function addFilesMtimes() : array
    {

        // Do not renew reading if reading has been already done.
        if ($this->elements === null) {
            $this->read();
        }

        // Find mtime for every file in elements.
        foreach ($this->elements as $uri => $element) {

            // Only for files.
            if ($element['isFile'] !== true) {
                continue;
            }

            // Save.
            $this->elements[$uri]['mtime']          = filemtime($this->getPath(true) . $uri);
            $this->elements[$uri]['mtimeFormatted'] = date('Y-m-d H:i:s', $this->elements[$uri]['mtime']);
        }

        return $this->elements;
    }

    /**
     * Reads contents of given dir (created because of for recursivenes, used with every call).
     *
     * @param string $deeper Deeper part of URI below Path.
     *
     * @since  v2.0
     * @return string[]
     */
    private function readRaw(string $deeper = '') : array
    {

        // Lvd.
        $result          = [];
        $readRecursively = ( isset($this->options[Path::DIR_READ_RECURSIVELY]) === true );
        $ignoreDirs      = ( isset($this->options[Path::DIR_READ_IGNORE_DIRS]) === true );
        $ignoreFiles     = ( isset($this->options[Path::DIR_READ_IGNORE_FILES]) === true );

        // Make deeper end with slash.
        if (empty($deeper) === false) {
            $deeper = rtrim($deeper, '\\') . '\\';
        }

        // Read all elements.
        $elements = scandir($this->getPath(true) . $deeper);

        // Scan all elements.
        foreach ($elements as $element) {

            // Ignore thos.
            if ($element === '.' || $element === '..') {
                continue;
            }

            // Lvd.
            $isDir  = ( is_dir($this->getPath(true) . $deeper . $element) === true );
            $isFile = ( is_file($this->getPath(true) . $deeper . $element) === true );

            // Add recursively.
            if ($readRecursively === true && $isDir === true) {
                $result = array_merge($result, $this->readRaw($deeper . $element));
            }

            // Add deeper dir.
            if (( $isFile === true && $ignoreFiles === false )
                || ( $isDir === true && $ignoreDirs === false )
            ) {
                $result[$deeper . $element] = [
                    'uri'    => $deeper . $element,
                    'isFile' => $isFile,
                    'isDir'  => $isDir,
                ];
            }
        }//end foreach

        return $result;
    }
}
