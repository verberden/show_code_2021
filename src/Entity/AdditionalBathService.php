<?php

namespace App\Entity;

use App\Repository\AdditionalBathServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=AdditionalBathServiceRepository::class)
 */
class AdditionalBathService
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"bathhouse:item"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"bathhouse:item"})
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"bathhouse:item"})
     */
    private $price;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"bathhouse:item"})
     */
    private $description;

    /**
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @ORM\OneToOne(targetEntity=MediaFile::class, cascade={"persist", "remove"})
     * @Groups({"bathhouse:item", "reservation:item"})
     */
    private $image;

    /**
     * @ORM\ManyToMany(targetEntity=Bathhouse::class, mappedBy="additionalServices")
     */
    private $bathhouses;

    /**
     * @ORM\OneToMany(targetEntity=BathReservationItems::class, mappedBy="additionalService")
     */
    private $bathReservationItems;

    public function __construct()
    {
        $this->bathhouses = new ArrayCollection();
        $this->bathReservationItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getImage(): ?MediaFile
    {
        return $this->image;
    }

    public function setImage(?MediaFile $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection|Bathhouse[]
     */
    public function getBathhouses(): Collection
    {
        return $this->bathhouses;
    }

    public function addBathhouse(Bathhouse $bathhouse): self
    {
        if (!$this->bathhouses->contains($bathhouse)) {
            $this->bathhouses[] = $bathhouse;
            $bathhouse->addAdditionalService($this);
        }

        return $this;
    }

    public function removeBathhouse(Bathhouse $bathhouse): self
    {
        if ($this->bathhouses->removeElement($bathhouse)) {
            $bathhouse->removeAdditionalService($this);
        }

        return $this;
    }

    /**
     * @return Collection|BathReservationItems[]
     */
    public function getBathReservationItems(): Collection
    {
        return $this->bathReservationItems;
    }

    public function addBathReservationItem(BathReservationItems $bathReservationItem): self
    {
        if (!$this->bathReservationItems->contains($bathReservationItem)) {
            $this->bathReservationItems[] = $bathReservationItem;
            $bathReservationItem->setAdditionalService($this);
        }

        return $this;
    }

    public function removeBathReservationItem(BathReservationItems $bathReservationItem): self
    {
        if ($this->bathReservationItems->removeElement($bathReservationItem)) {
            // set the owning side to null (unless already changed)
            if ($bathReservationItem->getAdditionalService() === $this) {
                $bathReservationItem->setAdditionalService(null);
            }
        }

        return $this;
    }
}
