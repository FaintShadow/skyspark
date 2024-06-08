<?php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class input_w_icon
{
    public string $value;
    public string $icon;
    public String $type='text';
    public string $name = '';
}