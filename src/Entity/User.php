<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function PHPUnit\Framework\isEmpty;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;
    #[ORM\Column(length: 255)]
    private ?string $firstName = null;
    #[ORM\Column(length: 255)]
    private ?string $lastName = null;


    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, DiscussionMessages>
     */
    #[ORM\OneToMany(targetEntity: DiscussionMessages::class, mappedBy: 'user')]
    private Collection $discussionMessages;

    /**
     * @var Collection<int, Discussion>
     */
    #[ORM\OneToMany(targetEntity: Discussion::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $discussions;

    public function __construct()
    {
        $this->discussionMessages = new ArrayCollection();
        $this->discussions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param string|null $firstName
     */
    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @param string|null $lastName
     */
    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @return list<string>
     * @see UserInterface
     *
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        //Admin role name : Celestrix
        //User role name: Astrobit

        if ( sizeof($roles) == 0 or in_array('DELETED', $roles)){
            $roles[] = 'ROLE_ASTROBIT';
        }

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, DiscussionMessages>
     */
    public function getDiscussionMessages(): Collection
    {
        return $this->discussionMessages;
    }

    public function addDiscussionMessage(DiscussionMessages $discussionMessage): static
    {
        if (!$this->discussionMessages->contains($discussionMessage)) {
            $this->discussionMessages->add($discussionMessage);
            $discussionMessage->setUserId($this);
        }

        return $this;
    }

    public function removeDiscussionMessage(DiscussionMessages $discussionMessage): static
    {
        if ($this->discussionMessages->removeElement($discussionMessage)) {
            // set the owning side to null (unless already changed)
            if ($discussionMessage->getUserId() === $this) {
                $discussionMessage->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Discussion>
     */
    public function getDiscussions(): Collection
    {
        return $this->discussions;
    }

    public function addDiscussion(Discussion $discussion): static
    {
        if (!$this->discussions->contains($discussion)) {
            $this->discussions->add($discussion);
            $discussion->setUser($this);
        }

        return $this;
    }

    public function removeDiscussion(Discussion $discussions): static
    {
        if ($this->discussions->removeElement($discussions)) {
            // set the owning side to null (unless already changed)
            if ($discussions->getUser() === $this) {
                $discussions->setUser(null);
            }
        }

        return $this;
    }

    public function removeAllDiscussions()
    {
        foreach ($this->discussions as $discussion){
            $this->removeDiscussion($discussion);
        }
    }

    public function removePersonalInfo(){
        $this->setFirstName('');
        $this->setLastName('');
        $this->setPassword('');
        $this->setRoles(["DELETED"]);
    }
    public function erasePersonalData(){
        $this->removeAllDiscussions();
        $this->removePersonalInfo();
    }

}
