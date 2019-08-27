<?php declare(strict_types=1);

namespace Przeslijmi\SiHDD;

use Przeslijmi\Sexceptions\Exceptions\ClassFopException;
use Przeslijmi\Sexceptions\Exceptions\MethodFopException;
use Przeslijmi\Sexceptions\Exceptions\ParamWrosynException;
use Przeslijmi\Sexceptions\Exceptions\RegexTestFailException;
use Przeslijmi\Sivalidator\GeoProgression;
use Przeslijmi\Sivalidator\RegEx;

/**
 * Object representing path (of Dir or File or alone).
 */
class Path
{

    /**
     * If used uris containing /../ or /./ will be accepted. Otherwise exception will be thrown.
     *
     * @var   int
     * @since v1.0
     */
    const ALLOW_DIR_DOTS               = 1;
    const ALLOW_NATIONAL_LETTERS_NAMES = 2;
    const ALLOW_SPACES_IN_NAMES        = 4;
    const DIR_READ_RECURSIVELY         = 8;
    const DIR_READ_IGNORE_DIRS         = 16;
    const DIR_READ_IGNORE_FILES        = 32;

    /**
     * String representation of whole path.
     *
     * @var   string
     * @since v1.0
     */
    private $path = '';

    /**
     * Parts of the path (dirs and optionally file as last one).
     *
     * @var   array
     * @since v1.0
     */
    private $parts = [];

    /**
     * Directory separator (taken from constant DIRECTORY_SEPARATOR).
     *
     * @var   sting
     * @since v1.0
     */
    private $sep = DIRECTORY_SEPARATOR;

    /**
     * Options sent on construction.
     *
     * @var   array
     * @since v1.0
     */
    protected $options = [];

    /**
     * Constructor.
     *
     * @param string  $path    Whole path.
     * @param integer $options Options transferred ad const (see above).
     *
     * @throws ClassFopException On creationOfPath when creation of path is not possible.
     * @since  v1.0
     */
    public function __construct(string $path, int $options = 0)
    {

        // Read options.
        if ($options > 0) {
            $this->options = array_fill_keys(GeoProgression::get($options), true);
        }

        // Save.
        $this->path  = str_replace([ '/', '\\' ], $this->sep, $path);
        $this->parts = explode($this->sep, $this->path);

        // Test.
        try {
            $this->testPath();
        } catch (ParamWrosynException $e) {
            throw (new ClassFopException('creationOfPath', $e))->addInfo('fullPath', $path);
        }
    }

    /**
     * Tests if this path leads to an existing resource.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isExisting() : bool
    {

        return file_exists($this->path);
    }

    /**
     * Tests if this path leads to an nonexisting resource.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isNotExisting() : bool
    {

        return ! ( $this->isExisting() );
    }

    /**
     * Tests if this path leads to a directory.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isDir() : bool
    {

        return ( file_exists($this->path) && is_dir($this->path) );
    }

    /**
     * Tests if this path leads not to a directory.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isNotDir() : bool
    {

        return ! ( $this->isDir() );
    }

    /**
     * Tests if this path leads to a file.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isFile() : bool
    {

        return ( file_exists($this->path) && is_file($this->path) );
    }

    /**
     * Tests if this path leads not to a file.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isNotFile() : bool
    {

        return ! ( $this->isFile() );
    }

    /**
     * Getter for `this->path`.
     *
     * @param boolean $enforceEndingSlash Optional, false. When set to true slash will be added at the end.
     *
     * @since  v1.0
     * @return string
     */
    public function getPath(bool $enforceEndingSlash = false) : string
    {

        if ($enforceEndingSlash === true) {
            $this->path = rtrim($this->path, $this->sep) . $this->sep;
        }

        return $this->path;
    }

    /**
     * Called to create all nonexisting directories on this path.
     *
     * @param boolean $createLastDirAlso Opt., false. If set to true last element of the path
     *                                   is also treated as a dir and created.
     *
     * @since  v1.0
     * @return void
     */
    public function createDirs(bool $createLastDirAlso = false) : void
    {

        // Calculate starting path (from cwd).
        $risingPath   = $this->calculateRisingPath();
        $partsButLast = array_slice($this->parts, 0, ( count($this->parts) - 1 ));
        $lastPart     = implode('', array_slice($this->parts, -1));

        // Parts from 0 to (n-1) have to be dirs if existing.
        foreach ($partsButLast as $partNo => $part) {

            // Increase.
            $risingPath .= $part;

            // Test.
            if (empty($risingPath) === false && file_exists($risingPath) === false) {
                mkdir($risingPath);
            }

            // Add separators.
            $risingPath .= $this->sep;
        }

        if ($createLastDirAlso === true && file_exists($risingPath . $lastPart) === false) {
            mkdir($risingPath . $lastPart);
        }
    }

    /**
     * Deletes empty (only empty) dirs in path, deeper then param.
     *
     * Usage example
     * ```
     * $path = new Path('existingAndProtected/sub1/sub11/sub111');
     * $path->deleteEmptyDirs(0); // will delete existingAndProtected/sub1/sub11/sub111
     * $path->deleteEmptyDirs(1); // will delete                      sub1/sub11/sub111
     * $path->deleteEmptyDirs(2); // will delete                           sub11/sub111
     * $path->deleteEmptyDirs(3); // will delete                                 sub111
     * ```
     *
     * @param integer $startingWithPart Obligatory - to delete whole path insert 0.
     *
     * @since  v1.0
     * @return void
     */
    public function deleteEmptyDirs(int $startingWithPart) : void
    {

        // Calculate starting path (from cwd).
        $risingPath    = $this->calculateRisingPath();
        $partsReversed = array_reverse($this->parts, true);

        // Analyze every dir in reversed order.
        foreach ($partsReversed as $partNo => $part) {

            // Do not delete earlier dirs than this index.
            if ($partNo < $startingWithPart) {
                break;
            }

            // Lvd.
            $fullPath = implode($this->sep, array_slice($this->parts, 0, ( $partNo + 1 )));

            // If this is already deleted (nonexisting) - then ignore.
            if (file_exists($fullPath) === false) {
                continue;
            }

            // If the dir is not empty - do NOT delete it.
            if (count(scandir($fullPath)) > 2) {
                break;
            }

            // Everything looks nice - delete.
            rmdir($fullPath);
        }//end foreach
    }

    /**
     * Calculate starting path (from cwd).
     *
     * When path begins with / - it will be use it is (nothing added).
     * When path begins not with / - current working dir (cwd) will be added.
     *
     * @since  v1.0
     * @return string
     */
    private function calculateRisingPath() : string
    {

        if (empty($this->parts[0]) === true) {
            $risingPath = '';
        } else {
            // It is still a string.
            $risingPath = rtrim(getcwd(), $this->sep) . $this->sep;
        }

        return $risingPath;
    }

    /**
     * Run several test on path given to constructor to check if path is proper.
     *
     * @throws ParamWrosynException When one of inner parts of path is an existing non-dir.
     * @since  v1.0
     * @return void
     */
    private function testPath() : void
    {

        // Every part has to be proper.
        foreach ($this->parts as $partNo => $part) {
            $this->isPartOfPathProper($partNo);
        }

        // Calculate starting path (from cwd).
        $risingPath = $this->calculateRisingPath();

        // Parts from 0 to (n-1) have to be dirs if existing.
        foreach (array_slice($this->parts, 0, ( count($this->parts) - 1 )) as $partNo => $part) {

            // Increase.
            $risingPath .= $part;

            // Test.
            if (file_exists($risingPath) === true && is_dir($risingPath) === false) {
                throw new ParamWrosynException('notLastPartOfPathHasToBeDirIfExists', $risingPath);
            }

            // Add separators.
            $risingPath .= $this->sep;
        }
    }

    /**
     * Check if one (each) part of path is proper.
     *
     * @param integer $partNo Counting from 0 part of a paht.
     * @param boolean $throw  Opt., true. Throws on error if set to true, otherwise return false.
     *
     * @throws ParamWrosynException When part of the path does not meat regex regulations.
     * @since  v1.0
     * @return boolean
     */
    private function isPartOfPathProper(int $partNo, bool $throw = true) : bool
    {

        // Lvd.
        $part   = $this->parts[$partNo];
        $result = (bool) false;

        // If this part is empty - it is ok.
        if (empty($part) === true) {
            return true;
        }

        // Lvd regex.
        $regex         = '';
        $regexNational = '';
        $regexSpaces   = '';

        if (isset($this->options[self::ALLOW_NATIONAL_LETTERS_NAMES]) === true) {
            $regexNational = 'ążśźęćńółĄŻŚŹĘĆŃÓŁ';
        }
        if (isset($this->options[self::ALLOW_SPACES_IN_NAMES]) === true) {
            $regexSpaces = '\ ';
        }
        $regex = '/^([a-zA-Z0-9_\-\.' . $regexNational . $regexSpaces . '\:])+$/';

        // Run regex test to make sure this is proper path part.
        try {
            $result = RegEx::ifMatches($part, $regex);
        } catch (RegexTestFailException $e) {
            if ($throw === true) {
                throw new ParamWrosynException('partOfPath', $part, $e);
            }
        }

        return $result;
    }
}
