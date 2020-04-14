<?php declare(strict_types=1);

namespace Przeslijmi\SiHDD;

use PHPUnit\Framework\TestCase;
use Przeslijmi\Sexceptions\Exceptions\ClassFopException;
use Przeslijmi\Sexceptions\Exceptions\FileAlrexException;
use Przeslijmi\Sexceptions\Exceptions\FileDonoexException;
use Przeslijmi\Sexceptions\Exceptions\PointerWrosynException;

/**
 * Methods for testing File class.
 */
final class FileTest extends TestCase
{

    /**
     * Test if File can be properly created, written and read.
     *
     * @return void
     */
    public function testProperCreation() : void
    {

        $file = new File('config\\.temp.' . rand(1000, 9999) . '.php');
        $file->setContents('contents');
        $file->create();
        $file->append(' nextContents');
        $file->appendLine('next line of contents');
        $file->readIfExists();

        $expectedContents  = 'contents nextContents';
        $expectedContents .= PHP_EOL;
        $expectedContents .= 'next line of contents';
        $this->assertEquals($expectedContents, $file->getContents());

        $this->assertTrue($file->isExisting());
        $this->assertFalse($file->isNotExisting());
        $this->assertFalse($file->isDir());
        $this->assertTrue($file->isNotDir());
        $this->assertTrue($file->isFile());
        $this->assertFalse($file->isNotFile());

        $file->deleteIfExists();
        $this->assertFalse($file->isExisting());
    }

    /**
     * Test if creating file name with wrong name throws.
     *
     * @return void
     */
    public function testIfWrongNameThrows() : void
    {

        $this->expectException(ClassFopException::class);

        $file = new File('config\\.temp    .php');
    }

    /**
     * Test if creating file name with accepted spaces in name throws.
     *
     * @return void
     */
    public function testIfNotWrongNameNotThrows1() : void
    {

        $path = 'config\\.temp    .php';

        $file = new File($path, File::ALLOW_SPACES_IN_NAMES);
        $this->assertEquals($path, $file->getPath());
    }

    /**
     * Test if creating file name with accepted polish letters in name throws.
     *
     * @return void
     */
    public function testIfNotWrongNameNotThrows2() : void
    {

        $path = 'config\\.temp.żądło.php';

        $file = new File($path, File::ALLOW_NATIONAL_LETTERS_NAMES);
        $this->assertEquals($path, $file->getPath());
    }

    /**
     * Test if creating file that duplicates name with an existing directory throws.
     *
     * @return void
     */
    public function testIfDirNameAsFileThrows() : void
    {

        $this->expectException(ClassFopException::class);

        // This is an existing dir - not a file.
        $file = new File('config');
    }

    /**
     * Test if reading nonexisting file throws.
     *
     * @return void
     */
    public function testIfReadingFromNonexistingFileThrows() : void
    {

        $this->expectException(FileDonoexException::class);

        // This is an nonexisting file - reading is impossible.
        $file = new File('config\\nonexisting_file.' . rand(1000, 9999) . '.nef');
        $file->read();
    }

    /**
     * Test if conditional reading from nonexsiting file returns empty string.
     *
     * @return void
     */
    public function testIfReadingFromNonexistingFilesisIgnored() : void
    {

        // This is an nonexisting file - reading is impossible.
        $file = new File('config\\nonexisting_file.' . rand(1000, 9999) . '.nef');

        $this->assertEquals('', $file->readIfExists());
    }

    /**
     * Test if reading from a non-file (eg. directory) throws.
     *
     * @return void
     */
    public function testIfReadingFromNonfileThrows() : void
    {

        // Lvd.
        $rand = rand(1000, 9999);

        // Create File.
        $file = new File('config\\test.' . $rand . '.txt');

        // Create Path to dir with identical name.
        $path = new Path('config\\test.' . $rand . '.txt');
        $path->createDirs(true);

        $this->expectException(PointerWrosynException::class);

        // That file has already become dir - reading is impossible.
        try {
            $file->read();
        } finally {

            // Delete dir.
            $path->deleteEmptyDirs(1);
        }
    }

    /**
     * Test if deleting nonexisting file throws.
     *
     * @return void
     */
    public function testIfDeletingOfNonexistingFileWillThrow() : void
    {

        $this->expectException(FileDonoexException::class);

        $file = new File('config\\nonexisting_file.' . rand(1000, 9999) . '.nef');
        $file->delete();
    }

    /**
     * Test if creation of existing file (overwritting) throws.
     *
     * @return void
     */
    public function testIfCreationOfExistingFileWillThrow() : void
    {

        $this->expectException(FileAlrexException::class);

        $file = new File('bootstrap.php');
        $file->create();
    }
}
