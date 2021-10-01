<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=CustomerRepository::class)
 * @UniqueEntity("phone")
 */
class Customer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"order:list","order:item", "schedule:item", "reservation:item"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"order:list", "order:item", "schedule:item", "reservation:item"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups({"order:list", "order:item", "schedule:item", "reservation:item"})
     */
    private $phone;

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
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="customer")
     */
    private $orders;

    /**
     * @ORM\ManyToOne(targetEntity=House::class, inversedBy="customers", cascade={"persist"})
     */
    private $house;

    /**
     * @ORM\OneToMany(targetEntity=BathSlot::class, mappedBy="customer")
     */
    private $bathSlots;

    /**
     * @ORM\OneToMany(targetEntity=BathReservation::class, mappedBy="customer")
     */
    private $bathReservations;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    public function __construct()
    {
        $this->houses = new ArrayCollection();
        $this->orders = new ArrayCollection();

        $this->setCreatedAt(new \DateTimeImmutable());
        $this->setUpdatedAt(new \DateTimeImmutable());
        $this->bathSlots = new ArrayCollection();
        $this->bathReservations = new ArrayCollection();
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

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
            $order->setCustomer($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getCustomer() === $this) {
                $order->setCustomer(null);
            }
        }

        return $this;
    }

    public function getHouse(): ?House
    {
        return $this->house;
    }

    public function setHouse(?House $house): self
    {
        $this->house = $house;

        return $this;
    }

    /**
     * @return Collection|BathSlot[]
     */
    public function getBathSlots(): Collection
    {
        return $this->bathSlots;
    }

    public function addBathSlots(BathSlot $bathSlots): self
    {
        if (!$this->bathSlots->contains($bathSlots)) {
            $this->bathSlots[] = $bathSlots;
            $bathSlots->setCustomer($this);
        }

        return $this;
    }

    public function removeBathSlots(BathSlot $bathSlots): self
    {
        if ($this->bathSlotss->removeElement($bathSlots)) {
            // set the owning side to null (unless already changed)
            if ($bathSlots->getCustomer() === $this) {
                $bathSlots->setCustomer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|BathReservation[]
     */
    public function getBathReservations(): Collection
    {
        return $this->bathReservations;
    }

    public function addBathReservation(BathReservation $bathReservation): self
    {
        if (!$this->bathReservations->contains($bathReservation)) {
            $this->bathReservations[] = $bathReservation;
            $bathReservation->setCustomer($this);
        }

        return $this;
    }

    public function removeBathReservation(BathReservation $bathReservation): self
    {
        if ($this->bathReservations->removeElement($bathReservation)) {
            // set the owning side to null (unless already changed)
            if ($bathReservation->getCustomer() === $this) {
                $bathReservation->setCustomer(null);
            }
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
