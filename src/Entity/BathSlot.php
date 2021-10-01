<?php

namespace App\Entity;

use App\Repository\BathSlotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=BathSlotRepository::class)
 * @ORM\Table(name="bath_slot", 
 *    uniqueConstraints={
 *        @UniqueConstraint(name="slot_unique", 
 *            columns={"time", "bathschedule_id"})
 *    }
 * )
 */
class BathSlot
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Groups({"reservation:item"})
     */
    private $time;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"schedule:item"})
     */
    private $isReserved;

    /**
     * @ORM\ManyToOne(targetEntity=BathSchedule::class, inversedBy="bathSlots")
     * @Groups({"schedule:item"})
     */
    private $bathschedule;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="bathSlotss", cascade={"persist"})
     */
    private $customer;

    /**
     * @ORM\ManyToOne(targetEntity=BathReservation::class, inversedBy="slots")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $bathReservation;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $reservedByAdmin;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"reservation:item"})
     */
    private $price;

    public function __construct()
    {
        $this->reservedByAdmin = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(string $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getIsReserved(): ?bool
    {
        return $this->isReserved;
    }

    public function setIsReserved(bool $isReserved): self
    {
        $this->isReserved = $isReserved;

        return $this;
    }

    public function getBathschedule(): ?BathSchedule
    {
        return $this->bathschedule;
    }

    public function setBathschedule(?BathSchedule $bathschedule): self
    {
        $this->bathschedule = $bathschedule;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

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

    public function getReservedByAdmin(): ?bool
    {
        return $this->reservedByAdmin;
    }

    public function setReservedByAdmin(bool $reservedByAdmin): self
    {
        $this->reservedByAdmin = $reservedByAdmin;

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
}
