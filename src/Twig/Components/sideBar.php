<?php
namespace App\Twig\Components;

use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class sideBar
{
    public Array $discussions;
    public User $user;
}