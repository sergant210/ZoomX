<?php
namespace Zoomx;

use modResponse;
use modX;
use SplFileInfo;
use Zoomx\Support\Repository;
use Zoomx\Exceptions\FileException;

class FileResponse extends modResponse
{
    /** @var string|SplFileInfo */
    protected $file;
    /** @var Repository */
    protected $headers;
    /** @var bool */
    protected $isAttachment = false;
    /** @var bool */
    protected $deleteFileAfterSend = false;
    /** @var string File name for downloading */
    protected $fileName;

    private $mustBeParsed = false;


    public function __construct(modX $modx, $file, $isAttachment = false, $deleteFileAfterSend = false) {
        parent::__construct($modx);

        $this->setFile($file);
        $this->isAttachment = $isAttachment;
        $this->deleteFileAfterSend = $deleteFileAfterSend;
        $this->headers = new Repository();
    }

    /**
     * Sets the file to stream.
     *
     * @param string|SplFileInfo $file The file to stream
     * @return $this
     *
     * @throws FileException
     */
    public function setFile($file)
    {
        if (!$file instanceof SplFileInfo) {
            $file = new SplFileInfo((string) $file);
        }

        $this->file = $file;

        return $this;
    }

    /**
     * @return Repository
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param bool|null $value
     * @return $this|bool
     */
    public function isAttachment(bool $value = null)
    {
        if ($value === null) {
            return $this->isAttachment;
        }

        $this->isAttachment = $value;

        return $this;
    }

    /**
     * @param bool|null $value
     * @return $this|bool
     */
    public function deleteFileAfterSend(bool $value = null)
    {
        if ($value === null) {
            return $this->deleteFileAfterSend;
        }

        $this->deleteFileAfterSend = $value;

        return $this;
    }

    /**
     * Gets the file.
     *
     * @return SplFileInfo The file to stream
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sends HTTP headers.
     *
     * @return $this
     */
    public function sendHeaders()
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        // headers
        foreach ($this->headers->all() as $name => $value) {
            $replace = 0 === strcasecmp($name, 'Content-Type');
            header($name . ': ' . $value, $replace);
        }

        return $this;
    }

    /**
     * Set the file name.
     * @param string $name
     * @return $this
     */
    public function setFileName(string $name)
    {
        if (!empty($name)) {
            $this->fileName = $name;
        }

        return $this;
    }

    /**
     * Get the name of the file.
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Rename the downloaded file.
     * @param string $name
     * @return $this
     */
    public function downloadAs(string $name)
    {
        $this->isAttachment = true;
        return $this->setFileName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function outputContent(array $options = [])
    {
        while (ob_get_level() && @ob_end_clean()) {}

        if (!$this->file->isFile()) {
            abortx(404, 'File doesn\'t exist.');
        }

        if (!$this->file->isReadable()) {
            throw new FileException('File must be readable.');
        }
        $filesize = filesize($this->file->getPathname());

        $headers = array_change_key_case($this->headers->all());
        if (!isset($headers['content-type'])) {
            $mimeType = $this->getFileMimeType();
            if ($mimeType === false) {
                throw new FileException('Content-Type is not set.');
            }
            $this->headers->add('Content-Type', $mimeType);
        }
        if ($this->isAttachment) {
            $this->headers->add('Content-Disposition', 'attachment; filename=' . $this->fileName ?? $this->file->getFilename());
        }

        $out = fopen('php://output', 'wb');
        $file = fopen($this->file->getPathname(), 'rb');

        if ($this->mustBeParsed) {
            $content = fread($file, $filesize);
            $content = parserx()->parse($content);
            $filesize = strlen($content);
        }

        if (!isset($headers['content-length'])) {
            $this->headers->add("Content-Length: " . $filesize);
        }
        $this->sendHeaders();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $this->mustBeParsed ? fwrite($out, $content) : stream_copy_to_stream($file, $out);

        fclose($out);
        fclose($file);

        if ($this->deleteFileAfterSend) {
            unlink($this->file->getPathname());
        }

        exit();
    }

    public function withHeaders(array $headers)
    {
        $this->headers->add($headers);
        return $this;
    }

    public function parse($value = true)
    {
        $this->mustBeParsed = true;
        return $this;
    }

    private function getFileMimeType()
    {
        $mimeType = (string)zoomx()->getContentTypeDetector()->detect();
        return $mimeType ?: mime_content_type($this->file->getPathname());
    }
    /**
     * Get a property.
     *
     * @param  string  $property
     * @return mixed
     */
    public function __get($property)
    {
        $method = 'get' . ucfirst($property);

        return method_exists($this, $method) ? $this->$method() : null;
    }
}