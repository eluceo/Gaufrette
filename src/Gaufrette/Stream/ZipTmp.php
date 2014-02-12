<?php

namespace Gaufrette\Stream;

use Gaufrette\Stream;
use Gaufrette\StreamMode;

class ZipTmp implements Stream
{
    /**
     * @var string
     */
    protected $tmpFilename;

    /**
     * @var Stream
     */
    protected $tmpStream;

    /**
     * @var \ZipArchive
     */
    protected $zipArchive;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var boolean
     */
    protected $synchronized;

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
        $this->synchronized = true;
        $this->mode = $mode;

        $exists = false !== $this->zipArchive->statName($this->key);

        if (($exists && !$mode->allowsExistingFileOpening())
            || (!$exists && !$mode->allowsNewFileOpening())) {
            return false;
        }

        if ($mode->impliesExistingContentDeletion()
            || (!$exists && $mode->allowsNewFileOpening())) {
            $this->zipArchive->addFromString($this->key, '');
        }

        $this->tmpFilename = tempnam(sys_get_temp_dir(), uniqid());
        $this->tmpStream = new Local($this->tmpFilename);
        $this->tmpStream->open(new StreamMode('w+'));
        $this->copyToTmp();

        if ($mode->impliesPositioningCursorAtTheBeginning()) {
            $this->tmpStream->seek(0);
        } elseif ($mode->impliesPositioningCursorAtTheEnd()) {
            $this->tmpStream->seek(0, SEEK_END);
        }
    }

    public function close()
    {
        if (!$this->synchronized) {
            $this->flush();
        }

        $this->tmpStream->close();
    }

    private function copyToTmp()
    {
        $zipUrl = 'zip://' . $this->zipArchive->filename . '#' . $this->key;
        $readStream = new Local($zipUrl);

        do {
            $this->tmpStream->write($readStream->read(8192));
        } while (!$readStream->eof());
    }

    private function copyToZip()
    {
        $this->zipArchive->addFile($this->tmpFilename, $this->key);
        $this->synchronized = true;
    }

    public function read($count)
    {
        if (false === $this->mode->allowsRead()) {
            throw new \LogicException('The stream does not allow read.');
        }

        return $this->tmpStream->read($count);
    }

    public function write($data)
    {
        if (false === $this->mode->allowsWrite()) {
            throw new \LogicException('The stream does not allow write.');
        }

        $this->synchronized = false;
        return $this->tmpStream->write($data);
    }

    public function flush()
    {
        if ($this->synchronized) {
            return true;
        }

        $this->tmpStream->flush();
        $this->copyToZip();
        $this->synchronized = true;

        return true;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return $this->tmpStream->seek($offset, $whence);
    }

    public function tell()
    {
        return $this->tmpStream->tell();
    }

    public function eof()
    {
        return $this->tmpStream->eof();
    }

    public function stat()
    {
        return $this->tmpStream->stat();
    }

    public function cast($castAs)
    {
        return false;
    }

    public function unlink()
    {
        if ($this->mode && $this->mode->impliesExistingContentDeletion()) {
            return $this->zipArchive->deleteName($this->key);
        }

        return false;
    }
}
