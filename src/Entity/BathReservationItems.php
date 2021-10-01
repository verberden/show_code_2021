<?php

namespace App\Entity;

use App\Repository\BathReservationItemsRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=BathReservationItemsRepository::class)
 */
class BathReservationItems
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"reservation:item"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=AdditionalBathService::class, inversedBy="bathReservationItems")
     * @Groups({"reservation:item"})
     */
    private $additionalService;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"reservation:item", "schedule:item"})
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"reservation:item", "schedule:item"})
     */
    private $price;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"reservation:item", "schedule:item"})
     */
    private $quantity;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"reservation:item", "schedule:item"})
     */
    private $cost;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity=BathReservation::class, inversedBy="additionalServiceItems")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $bathReservation;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdditionalService(): ?AdditionalBathService
    {
        return $this->additionalService;
    }

    public function setAdditionalService(?AdditionalBathService $additionalService): self
    {
        $this->additionalService = $additionalService;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(int $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getBathReservation(): ?BathReservation
    {
        return $this->bathReservation;
    }

    public function setBathReservation(?BathReservation $bathReservation): self
    {
        $this->bathReservation = $bathReservation;

        return $this;
    }
}
