<?php

namespace Gaufrette\Stream;

use Gaufrette\Stream;
use Gaufrette\StreamMode;

class Zip implements Stream
{
    /**
     * @var \ZipArchive
     */
    protected $zipArchive;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var Stream
     */
    protected $activeStream = null;

    /**
     * @var StreamMode
     */
    protected $mode;

    function __construct($zipArchive, $key)
    {
        $this->zipArchive = $zipArchive;
        $this->key = $key;
    }

    public function open(StreamMode $mode)
    {
//        if ($mode->allowsWrite() ||
//            $mode->isBinary() ||
//            $mode->allowsNewFileOpening()
//        ) {
            $this->activeStream = $this->createWriteStream($mode);
//        } else {
//            $this->activeStream = $this->createReadStream($mode);
//        }

        $this->activeStream->open($mode);
        $this->mode = $mode;

        return true;
    }

    private function createReadStream()
    {
        $zipUrl = 'zip://' . $this->zipArchive->filename . '#' . $this->key;
        return new Local($zipUrl);
    }

    private function createWriteStream()
    {
        return new ZipTmp($this->zipArchive, $this->key);
    }

    public function close()
    {
        if ($this->activeStream === null) {
            return false;
        }

        $close = $this->activeStream->close();

        if (!$close) {
            return false;
        }

        $this->activeStream = null;
        $this->mode = null;

        return true;
    }

    public function read($count)
    {
        return $this->activeStream->read($count);
    }

    public function write($data)
    {
        return $this->activeStream->write($data);
    }

    public function flush()
    {
        return $this->activeStream->flush();
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return $this->activeStream->seek($offset, $whence);
    }

    public function tell()
    {
        return $this->activeStream->tell();
    }

    public function eof()
    {
        return $this->activeStream->eof();
    }

    public function stat()
    {
        return $this->activeStream->stat();
    }

    public function cast($castAs)
    {
        return $this->activeStream->cast($castAs);
    }

    public function unlink()
    {
        return $this->activeStream->unlink();
    }
}
