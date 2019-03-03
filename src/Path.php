<?php

namespace Przeslijmi\SiHDD;

use Przeslijmi\Sexceptions\Exceptions\ClassFopException;
use Przeslijmi\Sexceptions\Exceptions\ParamWrosynException;
use Przeslijmi\Sexceptions\Exceptions\RegexTestFailException;
use Przeslijmi\Sivalidator\RegEx;

class Path
{

    /**
     * String representation of whole path.
     *
     * @var string
     */
    private $path = '';

    /**
     * Parts of the path (dirs and optionally file as last one).
     *
     * @var array
     */
    private $parts = [];

    /**
     * Directory separator (taken from constant DIRECTORY_SEPARATOR).
     *
     * @var sting
     */
    private $sep = DIRECTORY_SEPARATOR;

    /**
     * Constructor.
     *
     * @param string $path Whole path.
     *
     * @since v1.0
     */
    public function __construct(string $path)
    {

        // save
        $this->path = str_replace([ '/', '\\' ], $this->sep, $path);
        $this->parts = explode($this->sep, $this->path);

        // test
        try {
            $this->test();
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

        return !($this->isExisting());
    }

    /**
     * Tests if this path leads to a directory.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isDir() : bool
    {

        return (file_exists($this->path) && is_dir($this->path));
    }

    /**
     * Tests if this path leads not to a directory.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isNotDir() : bool
    {

        return !($this->isDir());
    }

    /**
     * Tests if this path leads to a file.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isFile() : bool
    {

        return (file_exists($this->path) && is_file($this->path));
    }

    /**
     * Tests if this path leads not to a file.
     *
     * @since  v1.0
     * @return boolean
     */
    public function isNotFile() : bool
    {

        return !($this->isFile());
    }

    /**
     * Getter for `this->path`.
     *
     * @since  v1.0
     * @return string
     */
    public function getPath() : string
    {

        return $this->path;
    }

    /**
     * Called to create all nonexisting directories on this path.
     *
     * @since  v1.0
     * @return string
     */
    public function createDirs() : void
    {

        // calculate starting path (from cwd)
        $risingPath = $this->calculateRisingPath();

        // parts from 0 to (n-1) have to be dirs if existing
        foreach (array_slice($this->parts, 0, (count($this->parts) - 1)) as $partNo => $part) {

            // increase
            $risingPath .= $part;

            // test
            if (file_exists($risingPath) === false) {
                mkdir($risingPath);
            }

            // add separators
            $risingPath .= $this->sep;
        }
    }

    /**
     * Calculate starting path (from cwd).
     *
     * When path begins with / - it will be use it is (nothing added).
     * When path begins not with / - current working dir (cwd) will be added.
     *
     * @return [type] [description]
     */
    private function calculateRisingPath() : string
    {

        if (empty($this->parts[0]) === true) {
            $risingPath = '';
        } else {
            $risingPath = rtrim(getcwd(), $this->sep) . $this->sep; // string
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
    private function test() : void
    {

        // every part has to be proper
        foreach ($this->parts as $partNo => $part) {
            $this->isPartOfPathProper($partNo);
        }

        // calculate starting path (from cwd)
        $risingPath = $this->calculateRisingPath();

        // parts from 0 to (n-1) have to be dirs if existing
        foreach (array_slice($this->parts, 0, (count($this->parts) - 1)) as $partNo => $part) {

            // increase
            $risingPath .= $part;

            // test
            if (file_exists($risingPath) === true && is_dir($risingPath) === false) {
                throw new ParamWrosynException('notLastPartOfPathHasToBeDirIfExists', $risingPath);
            }

            // add separators
            $risingPath .= $this->sep;
        }
    }

    /**
     * Check if one (each) part of path is proper.
     *
     * @param int  $partNo Counting from 0 part of a paht.
     * @param bool $throw  (opt., true) Throws on error if set to true, otherwise return false.
     *
     * @throws ParamWrosynException When part of the path does not meat regex regulations.
     * @since  v1.0
     * @return bool
     */
    private function isPartOfPathProper(int $partNo, bool $throw=true) : bool
    {

        // lvd
        $part = $this->parts[$partNo];
        $result = (bool) false;

        // if this part is empty - it is ok
        if (empty($part) === true) {
            return true;
        }

        // run regex test to make sure this is proper path part
        try {
            $result = RegEx::ifMatches($part, '/^([a-zA-Z0-9_\.])+$/');
        } catch (RegexTestFailException $e) {
            if ($throw === true) {
                throw new ParamWrosynException('partOfPath', $part, $e);
            }
        }

        return $result;
    }
}
