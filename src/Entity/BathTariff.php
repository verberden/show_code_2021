<?php

namespace App\Entity;

use App\Repository\BathTariffRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=BathTariffRepository::class)
 */
class BathTariff
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Assert\NotBlank
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Assert\NotBlank
     */
    private $endDate;

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
     * @ORM\Column(type="boolean", options={"default" : 1})
     */
    private $enabled;

    /**
     * @ORM\ManyToMany(targetEntity=Bathhouse::class, mappedBy="bathTariffs")
     */
    private $bathhouses;

    /**
     * @ORM\OneToMany(targetEntity=BathTariffItem::class, mappedBy="tarif", cascade={"persist"}, orphanRemoval=true)
     * @Assert\NotBlank
     */
    private $bathTariffItems;

    /**
     * @ORM\Column(type="integer")
     */
    private $priority;

    public function __construct()
    {
        $this->enabled = 1;
        $this->bathhouses = new ArrayCollection();
        $this->bathTariffItems = new ArrayCollection();
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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

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

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

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
        }

        return $this;
    }

    public function removeBathhouse(Bathhouse $bathhouse): self
    {
        $this->bathhouses->removeElement($bathhouse);

        return $this;
    }

    /**
     * @return Collection|BathTariffItem[]
     */
    public function getBathTariffItems(): Collection
    {
        return $this->bathTariffItems;
    }

    public function addBathTariffItem(BathTariffItem $bathTariffItem): self
    {
        if (!$this->bathTariffItems->contains($bathTariffItem)) {
            $this->bathTariffItems[] = $bathTariffItem;
            $bathTariffItem->setTarif($this);
        }

        return $this;
    }

    public function removeBathTariffItem(BathTariffItem $bathTariffItem): self
    {
        if ($this->bathTariffItems->removeElement($bathTariffItem)) {
            // set the owning side to null (unless already changed)
            if ($bathTariffItem->getTarif() === $this) {
                $bathTariffItem->setTarif(null);
            }
        }

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }
}
