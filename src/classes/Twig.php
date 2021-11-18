<?php

namespace _;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Twig
{
    protected $environment;
    protected $directory;
    protected $options = [];

    function __construct(string $path, array $options = [])
    {
        $this->setDirectory($path);
        $this->setOptions($options);
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function setDirectory($directory)
    {
        if (!is_dir($directory)) {
            throw new \Exception(sprintf('Template directory %s does not exist', $directory));
        }
        $this->directory = $directory;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    public function addFunction(string $name, callable $callable)
    {
        $this->environment()->addFunction(new \Twig\TwigFunction($name, $callable));
        return $this;
    }

    protected function environment(array $options = []): Environment
    {
        if ($this->environment instanceof Environment) {
            return $this->environment;
        }

        $loader = new FilesystemLoader($this->getDirectory());
        $this->environment = new Environment($loader, $options);
        return $this->environment;
    }

    function render(string $templateFilename, array $context): string
    {
        $templatePath = $this->getDirectory() . '/' . $templateFilename;
        if (!file_exists($templateFilename) && !file_exists($templatePath)) {
            throw new \Exception(sprintf('Template not found at %s', $templatePath));
        }
        $content = $this->environment()->render($templateFilename, $context);
        if (empty($content)) {
            return '';
        }
        return $this->css($templateFilename) . $content;
    }

    function echo(string $templateFilename, array $context): void
    {
        echo $this->render($templateFilename, $context);
    }

    function css(string $templateFilename): string
    {
        $addedCss = &$GLOBALS['_twig_css'];
        $css = '';
        $pathInfo = pathinfo($templateFilename);
        if (empty($pathInfo['filename'])) {
            return '';
        }

        $templateParts = explode('.', $pathInfo['filename']);
        $cssFiles = [];
        foreach ($templateParts as $cssStub) {
            $cssFiles[] = $cssStub;
            $cssName = implode('.', $cssFiles);
            $cssPath = $this->getDirectory() . '/' . $cssName . '.css';
            if (in_array($cssPath, (array) $addedCss) || !file_exists($cssPath)) {
                continue;
            }

            $css .= sprintf('<style>%s</style>', file_get_contents($cssPath));
            $addedCss[] = $cssPath;
        }
        return $css;
    }
}
