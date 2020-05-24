<?php declare(strict_types=1);

namespace Przeslijmi\SiHDD;

use Przeslijmi\SiHDD\Path;

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
class File extends Path
{

    /**
     * Contents of the file.
     *
     * @var string
     */
    private $contents = '';

    /**
     * Constructor.
     *
     * @param string  $fullPath Full path to the file.
     * @param integer $options  Options transferred ad const (see above).
     *
     * @throws ClassFopException With creationOfFile context when creation of ne Path object failed.
     * @throws ClassFopException With filePathCannotBeADirPath context when given path is directory.
     */
    public function __construct(string $fullPath, int $options = 0)
    {

        // Create Path.
        try {
            parent::__construct($fullPath, $options);
        } catch (ClassFopException $e) {
            throw (new ClassFopException('creationOfFile', $e))->addInfo('fullPath', $fullPath);
        }

        // Check if this is not dir path.
        if ($this->isDir() === true) {
            throw (new ClassFopException('filePathCannotBeANonFilePath'))->addInfo('fullPath', $fullPath);
        }
    }

    /**
     * File contents setter (not saves, only sets).
     *
     * @param string $contents Contents to be set (repleaced) in file.
     *
     * @return File
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
     */
    public function getContents() : string
    {

        return $this->contents;
    }

    /**
     * Reads file from location and returns contents.
     *
     * @return string
     * @throws FileDonoexException    When file does not exists.
     * @throws PointerWrosynException When the pointer points not to a file.
     */
    public function read() : string
    {

        if ($this->isNotExisting() === true) {
            throw new FileDonoexException('read', $this->getPath());
        } elseif ($this->isNotFile() === true) {
            throw (new PointerWrosynException('readFileButIsNotAFile'))->addInfo('fullPath', $this->getPath());
        } else {
            $this->contents = file_get_contents($this->getPath());
        }

        return $this->contents;
    }

    /**
     * Reads file only if exists, otherwise returns empty string.
     *
     * @return string
     */
    public function readIfExists() : string
    {

        if ($this->isFile() === true) {
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
     */
    public function save() : void
    {

        if ($this->isNotExisting() === true) {
            $this->createDirs();
        }

        file_put_contents($this->getPath(), $this->contents);
    }

    /**
     * Append (and saves) extra contents to the file.
     *
     * @param string $contents Contents to be added.
     *
     * @return void
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
     */
    public function delete() : void
    {

        if ($this->isNotExisting() === true) {
            throw new FileDonoexException('unableToDeleteNonexistingFile', $this->getPath());
        }

        unlink($this->getPath());
    }

    /**
     * Deletes file from location if it exists.
     *
     * @return void
     */
    public function deleteIfExists() : void
    {

        if ($this->isExisting() === true) {
            $this->delete();
        }
    }

    /**
     * Creates file - ie saves it with given contents only if the file not exists.
     *
     * @return void
     * @throws FileAlrexException On unableToCreateExistingFile.
     */
    public function create() : void
    {

        if ($this->isExisting() === true) {
            throw new FileAlrexException('unableToCreateExistingFile', $this->getPath());
        }

        $this->save();
    }
}
