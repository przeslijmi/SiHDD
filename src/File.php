<?php declare(strict_types=1);

namespace Przeslijmi\SiHDD;

use Przeslijmi\Sexceptions\Exceptions\ClassFopException;
use Przeslijmi\Sexceptions\Exceptions\FileAlrexException;
use Przeslijmi\Sexceptions\Exceptions\FileDonoexException;
use Przeslijmi\Sexceptions\Exceptions\PointerWrosynException;
use Przeslijmi\Sivalidator\RegEx;
use Przeslijmi\Sivalidator\GeoProgression;

/**
 * Serves File operations on HDD.
 *
 * ## Usage example
 * ```
 * $file = new File('config/file.txt');
 * $file->setContents('test');
 * $file->save();
 * ```
 */
class File
{

    /**
     * If used uris containing /../ or /./ will be accepted. Otherwis exception will be thrown.
     *
     * @var int
     */
    const ALLOW_DIR_DOTS               = 1;
    const ALLOW_NATIONAL_LETTERS_NAMES = 2;
    const ALLOW_SPACES_IN_NAMES        = 4;

    /**
     * Path object.
     *
     * @var   Path
     * @since v1.0
     */
    private $path;

    /**
     * Contents of the file.
     *
     * @var   string
     * @since v1.0
     */
    private $contents = '';

    /**
     * Options sent on construction.
     *
     * @var   array
     * @since v1.0
     */
    private $options = [];

    /**
     * Constructor.
     *
     * @param string  $fullPath Full path to the file.
     * @param integer $options  Options transferred ad const (see above).
     *
     * @throws ClassFopException With creationOfFile context when creation of ne Path object failed.
     * @throws ClassFopException With filePathCannotBeADirPath context when given path is directory.
     * @since  v1.0
     */
    public function __construct(string $fullPath, int $options = 0)
    {

        // Read options.
        if ($options > 0) {
            $this->options = array_fill_keys(GeoProgression::get($options), true);
        }

        // Save path.
        try {
            $this->path = new Path($fullPath, $options);
        } catch (ClassFopException $e) {
            throw (new ClassFopException('creationOfFile', $e))->addInfo('fullPath', $fullPath);
        }

        // Check if this is not dir path.
        if ($this->path->isDir() === true) {
            throw (new ClassFopException('filePathCannotBeADirPath'))->addInfo('fullPath', $fullPath);
        }
    }

    /**
     * File contents setter (not saves, only sets).
     *
     * @param string $contents Contents to be set (repleaced) in file.
     *
     * @return File
     * @since  v1.0
     */
    public function setContents(string $contents) : File
    {

        $this->contents = $contents;

        return $this;
    }

    /**
     * File contents getter (read file before use).
     *
     * @return string
     * @since  v1.0
     */
    public function getContents() : string
    {

        return $this->contents;
    }

    /**
     * Getter for Path object.
     *
     * @since  v1.0
     * @return Path
     */
    public function getPath() : Path
    {

        return $this->path;
    }

    /**
     * Reads file from location and returns contents.
     *
     * @return string
     * @throws FileDonoexException    When file does not exists.
     * @throws PointerWrosynException When the pointer points not to a file.
     * @since  v1.0
     */
    public function read() : string
    {

        if ($this->path->isNotExisting() === true) {
            throw new FileDonoexException('read', $this->path->getPath());
        } elseif ($this->path->isNotFile() === true) {
            throw (new PointerWrosynException('readFileButIsNotAFile'))->addInfo('fullPath', $this->path->getPath());
        } else {
            $this->contents = file_get_contents($this->path->getPath());
        }

        return $this->contents;
    }

    /**
     * Reads file only if exists, otherwise returns empty string.
     *
     * @return string
     * @since  v1.0
     */
    public function readIfExists() : string
    {

        if ($this->path->isFile() === true) {
            return $this->read();
        }

        $this->contents = '';

        return $this->contents;
    }

    /**
     * Saves file and creates deep dirs if needed.
     *
     * @return void
     * @throws ClassFopException On saveFailed.
     * @since  v1.0
     */
    public function save() : void
    {

        if ($this->path->isNotExisting() === true) {
            $this->path->createDirs();
        }

        file_put_contents($this->path->getPath(), $this->contents);
    }

    /**
     * Append (and saves) extra contents to the file.
     *
     * @param string $contents Contents to be added.
     *
     * @return void
     * @since  v1.0
     */
    public function append(string $contents) : void
    {

        $this->readIfExists();
        $this->contents .= $contents;
        $this->save();
    }

    /**
     * Append (and saves) extra line of contents to the file.
     *
     * @param string $contents Line contents to be added.
     *
     * @return void
     * @since  v1.0
     */
    public function appendLine(string $contents) : void
    {

        $this->append(PHP_EOL . $contents);
    }

    /**
     * Deletes file from location.
     *
     * @return void
     * @throws FileDonoexException On unableToDeleteNonexistingFile.
     * @since  v1.0
     */
    public function delete() : void
    {

        if ($this->path->isNotExisting() === true) {
            throw new FileDonoexException('unableToDeleteNonexistingFile', $this->path->getPath());
        }

        unlink($this->path->getPath());
    }

    /**
     * Deletes file from location if it exists.
     *
     * @return void
     * @since  v1.0
     */
    public function deleteIfExists() : void
    {

        if ($this->path->isExisting() === true) {
            $this->delete();
        }
    }

    /**
     * Creates file - ie saves it with given contents only if the file not exists.
     *
     * @return void
     * @throws FileAlrexException On unableToCreateExistingFile.
     * @since  v1.0
     */
    public function create() : void
    {

        if ($this->path->isExisting() === true) {
            throw new FileAlrexException('unableToCreateExistingFile', $this->path->getPath());
        }

        $this->save();
    }
}
