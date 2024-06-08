<?php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use function PHPUnit\Framework\isEmpty;

#[AsTwigComponent]
class labeled_Input
{
    public string $label;
    public String $type='text';
    public String $name = '';
    public String $value = '';
    public String $properties = '';
}