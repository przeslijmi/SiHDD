<?php

namespace Przeslijmi\SiHDD;

use Przeslijmi\Sexceptions\Exceptions\ClassFopException;
use Przeslijmi\Sexceptions\Exceptions\FileAlrexException;
use Przeslijmi\Sexceptions\Exceptions\FileDonoexException;
use Przeslijmi\Sexceptions\Exceptions\PointerWrosynException;
use Przeslijmi\Sivalidator\RegEx;

/**
 * Serves File operations on HDD.
 *
 * ## Usage example
 * ```
 * $file = new File('config/file.txt');
 * $file->setContents('test');
 * $file->save();
 * ```
 *
 * @version v1.0
 */
class File
{

    /**
     * Path object.
     *
     * @var Path
     * @since v1.0
     */
    private $path;

    /**
     * Contents of the file.
     *
     * @var string
     * @since v1.0
     */
    private $contents = '';

    /**
     * Constructor.
     *
     * @param string $fullPath [description]
     * @throws ClassFopException creationOfFile On wrong path.
     * @throws ClassFopException filePathCannotBeADirPath
     * @since v1.0
     */
    public function __construct(string $fullPath)
    {

        // save path
        try {
            $this->path = new Path($fullPath);
        } catch (ClassFopException $e) {
            throw (new ClassFopException('creationOfFile', $e))->addInfo('fullPath', $fullPath);
        }

        // check if this is not dir path
        if ($this->path->isDir()) {
            throw (new ClassFopException('filePathCannotBeADirPath'))->addInfo('fullPath', $fullPath);
        }
    }

    /**
     * File contents setter (not saves, only sets).
     *
     * @param string $contents
     * @return File
     * @since v1.0
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
     * @since v1.0
     */
    public function getContents() : string
    {

        return $this->contents;
    }

    /**
     * Reads file from location and returns contents.
     *
     * @return string
     * @throws FileDonoexException
     * @throws PointerWrosynException readFileButIsNotAFile
     * @throws ClassFopException
     * @since v1.0
     */
    public function read() : string
    {

        if ($this->path->isNotExisting()) {
            throw new FileDonoexException('read', $this->path->getPath());
        } elseif ($this->path->isNotFile()) {
            throw (new PointerWrosynException('readFileButIsNotAFile'))->addInfo('fullPath', $this->path->getPath());
        } else {

            try {
                $this->contents = file_get_contents($this->path->getPath());
            } catch (\Exception $e) {
                throw (new ClassFopException('readingOfTheFile', $e))->addInfo('fullPath', $this->path->getPath());
            }
        }

        return $this->contents;
    }

    /**
     * Reads file only if exists, otherwise returns empty string.
     *
     * @return string
     * @since v1.0
     */
    public function readIfExists() : string
    {

        if ($this->path->isFile()) {
            return $this->read();
        }

        return '';
    }

    /**
     * Saves file and creates deep dirs if needed.
     *
     * @return void
     * @throws ClassFopException savefailed
     * @since v1.0
     */
    public function save() : void
    {

        if ($this->path->isNotExisting()) {
            $this->path->createDirs();
        }

        try {
            file_put_contents($this->path->getPath(), $this->contents);
        } catch (\Exception $e) {
            throw new ClassFopException('saveFailed', $this->path->getPath(), $e);
        }
    }

    /**
     * Append (and saves) extra contents to the file.
     *
     * @param string $contents
     * @return void
     * @since v1.0
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
     * @param string $contents
     * @return void
     * @since v1.0
     */
    public function appendLine(string $contents) : void
    {

        $this->append(PHP_EOL . $contents);
    }

    /**
     * Deletes file from location.
     *
     * @return void
     * @throws FileDonoexException unableToDeleteNonexistingFile
     * @since v1.0
     */
    public function delete() : void
    {

        if ($this->path->isNotExisting()) {
            throw new FileDonoexException('unableToDeleteNonexistingFile', $this->path->getPath());
        }

        unlink($this->path->getPath());
    }

    /**
     * Deletes file from location if it exists.
     *
     * @return void
     * @since v1.0
     */
    public function deleteIfExists() : void
    {

        if ($this->path->isExisting()) {
            $this->delete();
        }
    }

    /**
     * Creates file - ie saves it with given contents only if the file not exists.
     *
     * @return void
     * @throws FileAlrexException
     * @since v1.0
     */
    public function create() : void
    {

        if ($this->path->isExisting()) {
            throw new FileAlrexException('unableToCreateExistingFile', $this->path->getPath());
        }

        $this->save();
    }
}
