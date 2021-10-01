<?php

namespace App\Entity;

use App\Repository\BathhouseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=BathhouseRepository::class)
 */
class Bathhouse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"schedule:item", "bathhouse:item"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"schedule:item", "bathhouse:item"})
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"bathhouse:item"})
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Gallery", mappedBy="bathhouse", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\OrderBy({"position" = "ASC"})
     * @Groups({"bathhouse:item"})
     */
    private $galleries;

    /**
     * @ORM\OneToOne(targetEntity=MediaFile::class, cascade={"persist", "remove"})
     * @Groups({"bathhouse:item"})
     */
    private $image;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"bathhouse:item"})
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
     * @ORM\Column(type="integer")
     */
    private $slotTime;

    /**
     * @ORM\OneToMany(targetEntity=BathSchedule::class, mappedBy="bathhouse", cascade={"persist"}, fetch="EAGER")
     */
    private $bathSchedules;

    /**
     * @ORM\Column(type="integer", options={"default" : 1})
     * @Groups({"schedule:item"})
     */
    private $position;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"schedule:item", "bathhouse:item"})
     */
    private $memo;

    /**
     * @ORM\ManyToMany(targetEntity=AdditionalBathService::class, inversedBy="bathhouses")
     * @Groups({"bathhouse:item"})
     */
    private $additionalServices;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToMany(targetEntity=BathTariff::class, inversedBy="bathhouses")
     */
    private $bathTariffs;

    public function __construct()
    {
      $this->galleries = new ArrayCollection();
      $this->bathSchedules = new ArrayCollection();
      $this->additionalService = new ArrayCollection();
      $this->bathTariffs = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

      /**
   * @return ArrayCollection|Gallery[]
   */
  public function getGalleries(): Collection
  {
    return $this->galleries;
  }

  public function addGallery(Gallery $gallery): self
  {
    if (!$this->galleries->contains($gallery)) {
      $this->galleries[] = $gallery;
      $gallery->setBathhouse($this);
    }

    return $this;
  }

    public function removeGallery(Gallery $gallery): self
    {
        if ($this->galleries->contains($gallery)) {
            $this->galleries->removeElement($gallery);
            // set the owning side to null (unless already changed)
            if ($gallery->getBathhouse() === $this) {
            $gallery->setBathhouse(null);
            }
        }

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

    public function getSlotTime(): ?int
    {
        return $this->slotTime;
    }

    public function setSlotTime(int $slotTime): self
    {
        $this->slotTime = $slotTime;

        return $this;
    }

    /**
     * @return Collection|BathSchedule[]
     */
    public function getBathSchedules(): Collection
    {
        return $this->bathSchedules;
    }

    public function addBathSchedule(BathSchedule $bathSchedule): self
    {
        if (!$this->bathSchedules->contains($bathSchedule)) {
            $this->bathSchedules[] = $bathSchedule;
            $bathSchedule->setBathhouse($this);
        }

        return $this;
    }

    public function removeBathSchedule(BathSchedule $bathSchedule): self
    {
        if ($this->bathSchedules->removeElement($bathSchedule)) {
            // set the owning side to null (unless already changed)
            if ($bathSchedule->getBathhouse() === $this) {
                $bathSchedule->setBathhouse(null);
            }
        }

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

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setMemo(?string $memo): self
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * @return Collection|AdditionalBathService[]
     */
    public function getAdditionalServices(): ?Collection
    {
        return $this->additionalServices;
    }

    public function addAdditionalService(AdditionalBathService $additionalService): self
    {
        if (!$this->additionalServices->contains($additionalService)) {
            $this->additionalServices[] = $additionalService;
        }

        return $this;
    }

    public function removeAdditionalService(AdditionalBathService $additionalService): self
    {
        $this->additionalServices->removeElement($additionalService);

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection|BathTariff[]
     */
    public function getBathTariffs(): Collection
    {
        return $this->bathTariffs;
    }

    public function addBathTariff(BathTariff $bathTariff): self
    {
        if (!$this->bathTariffs->contains($bathTariff)) {
            $this->bathTariffs[] = $bathTariff;
            $bathTariff->addBathhouse($this);
        }

        return $this;
    }

    public function removeBathTariff(BathTariff $bathTariff): self
    {
        if ($this->bathTariffs->removeElement($bathTariff)) {
            $bathTariff->removeBathhouse($this);
        }

        return $this;
    }
}
