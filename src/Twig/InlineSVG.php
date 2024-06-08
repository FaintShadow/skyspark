<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\HttpKernel\KernelInterface;

class InlineSVG extends AbstractExtension
{
    private $projectDir;

    public function __construct(KernelInterface $kernel)
    {
        $this->projectDir = $kernel->getProjectDir();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_inline_svg', [$this, 'renderInlineSvg']),
        ];
    }

    public function renderInlineSvg(string $filePath): string
    {
        $fullPath = $this->projectDir . '/public/' . $filePath;
        if (!file_exists($fullPath)) {
            throw new \Exception("File not found: " . $fullPath);
        }
        return file_get_contents($fullPath);
    }
}
