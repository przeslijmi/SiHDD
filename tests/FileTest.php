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

        $file = new File('config/.temp.' . rand(1000, 9999) . '.php');
        $file->setContents('contents');
        $file->create();
        $file->append(' nextContents');
        $file->appendLine('next line of contents');
        $file->readIfExists();

        $expectedContents  = 'contents nextContents';
        $expectedContents .= PHP_EOL;
        $expectedContents .= 'next line of contents';
        $this->assertEquals($expectedContents, $file->getContents());

        $this->assertTrue($file->getPath()->isExisting());
        $this->assertFalse($file->getPath()->isNotExisting());
        $this->assertFalse($file->getPath()->isDir());
        $this->assertTrue($file->getPath()->isNotDir());
        $this->assertTrue($file->getPath()->isFile());
        $this->assertFalse($file->getPath()->isNotFile());

        $file->deleteIfExists();
        $this->assertFalse($file->getPath()->isExisting());
    }

    public function testIfWrongNameThrows() : void
    {

        $this->expectException(ClassFopException::class);

        $file = new File('config/.temp    .php');
    }

    public function testIfDirNameAsFileThrows() : void
    {

        $this->expectException(ClassFopException::class);

        // This is an existing dir - not a file.
        $file = new File('config');
    }

    public function testIfReadingFromNonexistingFileThrows() : void
    {

        $this->expectException(FileDonoexException::class);

        // This is an nonexisting file - reading is impossible.
        $file = new File('config/nonexisting_file.' . rand(1000, 9999) . '.nef');
        $file->read();
    }

    public function testIfReadingFromNonexistingFilesisIgnored() : void
    {

        // This is an nonexisting file - reading is impossible.
        $file = new File('config/nonexisting_file.' . rand(1000, 9999) . '.nef');

        $this->assertEquals('', $file->readIfExists());
    }

    public function testIfReadingFromNonfileThrows() : void
    {

        // Lvd.
        $rand = rand(1000, 9999);

        // Create File.
        $file = new File('config/test.' . $rand . '.txt',);

        // Create Path to dir with identical name.
        $path = new Path('config/test.' . $rand . '.txt');
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

    public function testIfDeletingOfNonexistingFileWillThrow() : void
    {

        $this->expectException(FileDonoexException::class);

        $file = new File('config/nonexisting_file.' . rand(1000, 9999) . '.nef');
        $file->delete();
    }

    public function testIfCreationOfExistingFileWillThrow() : void
    {

        $this->expectException(FileAlrexException::class);

        $file = new File('config/.config.php');
        $file->create();
    }
}
