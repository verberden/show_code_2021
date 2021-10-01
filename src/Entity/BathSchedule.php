<?php

namespace App\Entity;

use App\Repository\BathScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=BathScheduleRepository::class)
 */
class BathSchedule
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $date;

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
     * @ORM\ManyToOne(targetEntity=Bathhouse::class, inversedBy="bathSchedules")
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $bathhouse;

    /**
     * @ORM\OneToMany(targetEntity=BathSlot::class, mappedBy="bathschedule", cascade={"persist"})
     */
    private $bathSlots;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     * @Groups({"schedule:item"})
     */
    private $closeForService;

    /**
     * @ORM\Column(type="string", length=255, options={"default" : "09:00"})
     */
    private $startTime;

    /**
     * @ORM\Column(type="string", length=255, options={"default" : "00:00"})
     */
    private $endTime;

    public function __construct()
    {
        $this->bathSlots = new ArrayCollection();
        $this->closeForService = 0;
        $this->startTime = "09:00";
        $this->endTime = "00:00";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

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

    public function getBathhouse(): ?Bathhouse
    {
        return $this->bathhouse;
    }

    public function setBathhouse(?Bathhouse $bathhouse): self
    {
        $this->bathhouse = $bathhouse;

        return $this;
    }

    /**
     * @return Collection|BathSlots[]
     */
    public function getBathSlots(): Collection
    {
        return $this->bathSlots;
    }

    public function addBathSlot(BathSlot $bathSlot): self
    {
        if (!$this->bathSlots->contains($bathSlot)) {
            $this->bathSlots[] = $bathSlot;
            $bathSlot->setBathschedule($this);
        }

        return $this;
    }

    public function removeBathSlot(BathSlot $bathSlot): self
    {
        if ($this->bathSlots->removeElement($bathSlot)) {
            // set the owning side to null (unless already changed)
            if ($bathSlot->getBathschedule() === $this) {
                $bathSlot->setBathschedule(null);
            }
        }

        return $this;
    }

    public function getCloseForService(): ?bool
    {
        return $this->closeForService;
    }

    public function setCloseForService(bool $closeForService): self
    {
        $this->closeForService = $closeForService;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }
}
