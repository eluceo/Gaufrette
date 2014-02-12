<?php

namespace Gaufrette\Functional\FileStream;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Zip as ZipAdapter;

class ZipTest extends FunctionalTestCase
{
    protected $directory;

    public function setUp()
    {
        $this->directory = __DIR__.DIRECTORY_SEPARATOR.'filesystem';
        @mkdir($this->directory);
        @chmod($this->directory, 0777);
        $this->filesystem = new Filesystem(new ZipAdapter($this->directory.DIRECTORY_SEPARATOR.'test.zip'));

        $this->registerLocalFilesystemInStream();
    }

    public function tearDown()
    {
        if (is_file($file = $this->directory.DIRECTORY_SEPARATOR.'test.zip')) {
            @unlink($file);
        }
        if (is_dir($this->directory)) {
            @rmdir($this->directory);
        }
    }
}
