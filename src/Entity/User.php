<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity("login")
 * @ORM\Table(indexes={@ORM\Index(name="telegram_chat_id_idx", columns={"telegram_chat_id"})})
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"telegram_chat_id", "telegram_user_id"})})
 * @UniqueEntity("telegramHash")
 * @property string $telegramText
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const ROLE_ADMIN_CAFE = 'ROLE_ADMIN_CAFE';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    const ROLE_DELIVERY_MAN = 'ROLE_DELIVERY_MAN';

    public const ROLES = [
        self::ROLE_ADMIN_CAFE => 'Администратор кафе',
        self::ROLE_ADMIN => 'Администратор',
        self::ROLE_SUPER_ADMIN => 'Администратор системы',
        self::ROLE_DELIVERY_MAN => 'Доставщик',
    ];

    public const ROLES_FOR_SELECT = [
        self::ROLE_ADMIN_CAFE => 'Администратор кафе',
        self::ROLE_ADMIN => 'Администратор',
        self::ROLE_DELIVERY_MAN => 'Доставщик',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $enabled;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $login;

    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="deliveryMan")
     */
    private $orders;

        /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telegramChatId;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $telegramHash;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telegramUserId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $enableBathNotification;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $enableOrderNotification;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->telegramHash = bin2hex(random_bytes(12));
        $this->enableBathNotification = false;
        $this->enableOrderNotification = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->login;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        // $roles[] = self::ROLE_ADMIN_CAFE;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
    /**
     * @param $role
     *
     * @return $this
     */
    public function addRole($role)
    {
        $this->roles[] = $role;
        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    /**
     * @return Collection|Order[]
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setDeliveryMan($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getDeliveryMan() === $this) {
                $order->setDeliveryMan(null);
            }
        }

        return $this;
    }

    public function getTelegramChatId(): ?string
    {
        return $this->telegramChatId;
    }

    public function setTelegramChatId(?string $telegramChatId): self
    {
        $this->telegramChatId = $telegramChatId;

        return $this;
    }

    public function getTelegramHash(): ?string
    {
        return $this->telegramHash;
    }

    public function setTelegramHash(string $telegramHash): self
    {
        $this->telegramHash = $telegramHash;

        return $this;
    }

    public function getTelegramUserId(): ?string
    {
        return $this->telegramUserId;
    }

    public function setTelegramUserId(?string $telegramUserId): self
    {
        $this->telegramUserId = $telegramUserId;

        return $this;
    }

    public function getTelegramText(): ?string
    {
        return $this->telegramHash ? '/register '.$this->telegramHash : null ;
    }

    public function generateTelegramHash(): void
    {
        $this->telegramHash = bin2hex(random_bytes(12));
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEnableBathNotification(): ?bool
    {
        return $this->enableBathNotification;
    }

    public function setEnableBathNotification(bool $enableBathNotification): self
    {
        $this->enableBathNotification = $enableBathNotification;

        return $this;
    }

    public function getEnableOrderNotification(): ?bool
    {
        return $this->enableOrderNotification;
    }

    public function setEnableOrderNotification(bool $enableOrderNotification): self
    {
        $this->enableOrderNotification = $enableOrderNotification;

        return $this;
    }
}
