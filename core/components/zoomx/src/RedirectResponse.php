<?php
namespace Zoomx;

use InvalidArgumentException;
use modResponse;
use modX;
use Zoomx\Support\Repository;

class RedirectResponse extends modResponse
{
    /** @var string */
    protected $targetUrl;
    /** @var Repository */
    protected $headers;
    /** @var int  */
    protected $statusCode = 302;
    /** @var string[] */
    public $statusTexts = [
        201 => 'Created',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
    ];

    /**
     * Creates a redirect response so that it conforms to the rules defined for a redirect status code.
     *
     * @param modX $modx
     * @param string $url
     * @param int $status
     * @param array $headers
     *
     * @throws InvalidArgumentException
     */
    public function __construct(modX $modx, string $url = '', int $status = 0, array $headers = [])
    {
        parent::__construct($modx);

        $routeParams = zoomx('router')->getRouteParams();

        $url = $url ?: @$routeParams['redirect']['targetUrl'];
        $this->setTargetUrl($url);

        if (!empty($status)) {
            $this->statusCode = $status;
        } else {
            $this->statusCode = $routeParams['redirect']['status'] ?? $this->statusCode;
        }

        if (!$this->isRedirectStatus()) {
            throw new InvalidArgumentException($modx->lexicon('zoomx_wrong_redirect_status', ['status' => $this->statusCode]));
        }

        $this->headers = new Repository();
        if (!empty($headers)) {
            $this->headers->add($headers);
        }
    }

    /**
     * Returns the target URL.
     *
     * @return string target URL
     */
    public function getTargetUrl()
    {
        return $this->targetUrl;
    }

    /**
     * Sets the redirect target of this response.
     *
     * @param string $url The URL to redirect to
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setTargetUrl($url)
    {
        if (empty($url)) {
            throw new InvalidArgumentException($this->modx->lexicon('zoomx_redirect_to_empty_url'));
        }

        $this->targetUrl = $url;

        return $this;
    }

    /**
     * Is the response a redirect of some form?
     * @return bool
     */
    public function isRedirectStatus()
    {
        return in_array($this->statusCode, [201, 301, 302, 303, 307, 308]);
    }

    /**
     * {@inheritDoc}
     */
    public function outputContent(array $options = [])
    {
        $this->parseTagretUrl();
        if (empty($this->targetUrl)) {
            throw new InvalidArgumentException('Cannot redirect to an empty URL.');
        }
        $this->sendHeaders();
        $this->sendRedirect($this->targetUrl, ['responseCode' => $this->getResponseHeader()]);
    }

    protected function getResponseHeader()
    {
        return sprintf('%s %s %s', $_SERVER['SERVER_PROTOCOL'], $this->statusCode, $this->statusTexts[$this->statusCode]);
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

    protected function parseTagretUrl()
    {

        foreach (zoomx('router')->getRouteVars() as $var => $value) {
            $this->targetUrl = str_replace('{$' . $var . '}', $value, $this->targetUrl);
        }
    }

    public function __invoke()
    {
        $this->outputContent();
    }
}
