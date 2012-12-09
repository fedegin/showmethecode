<?php

namespace SMTC\MainBundle\Twig\Extension;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\TwigBundle\Loader\FilesystemLoader;
use CG\Core\ClassUtils;
use SMTC\MainBundle\Service\GithubLocator;

class ShowMeTheCodeExtension extends \Twig_Extension
{
    protected $loader;
    protected $controller;
    protected $githubLocator;

    public function __construct(FilesystemLoader $loader, GithubLocator $githubLocator)
    {
        $this->loader = $loader;
        $this->githubLocator = $githubLocator;
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'code' => new \Twig_Function_Method($this, 'getCode', array('is_safe' => array('html'))),
        );
    }

    public function getCode($template)
    {
        $controllerLink = $this->githubLocator->getControllerLink($this->controller);
        $templateLink = $this->githubLocator->getTemplateLink($template);

        $controller = htmlspecialchars($this->getControllerCode(), ENT_QUOTES, 'UTF-8');
        $template = htmlspecialchars($this->getTemplateCode($template), ENT_QUOTES, 'UTF-8');

        // remove the code block
        $template = str_replace('{% set code = code(_self) %}', '', $template);

        return <<<EOF
<h4><strong>Controller Code - <a href="$controllerLink">Github</a></strong></h4>
<pre class="prettyprint">$controller</pre>

<h4><strong>Template Code - <a href="$templateLink">Github</a></strong></h4>
<pre class="prettyprint">$template</pre>
EOF;
    }

    protected function getControllerCode()
    {
        $class = get_class($this->controller[0]);
        if (class_exists('CG\Core\ClassUtils')) {
            $class = ClassUtils::getUserClass($class);
        }

        $r = new \ReflectionClass($class);
        $m = $r->getMethod($this->controller[1]);

        $code = file($r->getFilename());

        return '    '.$m->getDocComment()."\n".implode('', array_slice($code, $m->getStartline() - 1, $m->getEndLine() - $m->getStartline() + 1));
    }

    protected function getTemplateCode($template)
    {
        return $this->loader->getSource($template->getTemplateName());
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'demo';
    }
}