<?php

namespace App\Entity;

use App\Repository\BathTariffItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=BathTariffItemRepository::class)
 */
class BathTariffItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"tarifItem:item"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"tarifItem:item"})
     */
    private $timeStart;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"tarifItem:item"})
     */
    private $timeEnd;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @Groups({"tarifItem:item"})
     */
    private $price;

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
     * @ORM\ManyToOne(targetEntity=BathTariff::class, inversedBy="bathTariffItems")
     */
    private $tarif;

    /**
     * @ORM\OneToMany(targetEntity=TariffItemDay::class, mappedBy="bathTariffItem", orphanRemoval=true, cascade={"persist"})
     * @Groups({"tarifItem:item"})
     */
    private $days;

    public function __construct()
    {
        $this->days = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeStart(): ?string
    {
        return $this->timeStart;
    }

    public function setTimeStart(string $timeStart): self
    {
        $this->timeStart = $timeStart;

        return $this;
    }

    public function getTimeEnd(): ?string
    {
        return $this->timeEnd;
    }

    public function setTimeEnd(string $timeEnd): self
    {
        $this->timeEnd = $timeEnd;

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

    public function getTarif(): ?BathTariff
    {
        return $this->tarif;
    }

    public function setTarif(?BathTariff $tarif): self
    {
        $this->tarif = $tarif;

        return $this;
    }

    /**
     * @return Collection|TariffItemDay[]
     */
    public function getDays(): Collection
    {
        return $this->days;
    }

    public function addDay(TariffItemDay $day): self
    {
        if (!$this->days->contains($day)) {
            $this->days[] = $day;
            $day->setBathTariffItem($this);
        }

        return $this;
    }

    public function removeDay(TariffItemDay $day): self
    {
        if ($this->days->removeElement($day)) {
            // set the owning side to null (unless already changed)
            if ($day->getBathTariffItem() === $this) {
                $day->setBathTariffItem(null);
            }
        }

        return $this;
    }
}
