<?php

namespace App\Entity;

use App\Repository\BathReservationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=BathReservationRepository::class)
 * @property string $statusText
 */
class BathReservation
{
    public const PAYMENT_LIFETIME_MINUTES = 20;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $id;

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
     * @ORM\OneToMany(targetEntity=BathSlot::class, mappedBy="bathReservation")
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $slots;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"schedule:item", "reservation:item"})
     * 
     */
    private $cost;

    /**
     * @ORM\Column(type="integer", options={"default" : 0}, nullable=true)
     */
    private $paymentSum;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $hash;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity=BathReservationItems::class, mappedBy="bathReservation", orphanRemoval=true, cascade={"persist"})
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $additionalServiceItems;

    /**
     * @ORM\OneToMany(targetEntity=BathPayment::class, mappedBy="bathReservation", cascade={"persist"})
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $bathPayments;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="bathReservations", cascade={"persist"})
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $customer;

    /**
     * @ORM\Column(type="date_immutable")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $customerName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $customerPhone;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $bathName;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"schedule:item", "reservation:item"})
     */
    private $expiredAt;

    public function __construct()
    {
        $this->slots = new ArrayCollection();
        $this->additionalServiceItems = new ArrayCollection();
        $this->hash = bin2hex(random_bytes(12));
        $this->bathPayments = new ArrayCollection();
        $this->expiredAt = (new DateTimeImmutable())->modify(sprintf("+%d minutes", self::PAYMENT_LIFETIME_MINUTES));
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection|BathSlot[]
     */
    public function getSlots(): Collection
    {
        return $this->slots;
    }

    public function addSlot(BathSlot $slot): self
    {
        if (!$this->slots->contains($slot)) {
            $this->slots[] = $slot;
            $slot->setBathReservation($this);
        }

        return $this;
    }

    public function removeSlot(BathSlot $slot): self
    {
        if ($this->slots->removeElement($slot)) {
            // set the owning side to null (unless already changed)
            if ($slot->getBathReservation() === $this) {
                $slot->setBathReservation(null);
            }
        }

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

    public function getPaymentSum(): ?int
    {
        return $this->paymentSum;
    }

    public function setPaymentSum(int $paymentSum): self
    {
        $this->paymentSum = $paymentSum;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
    
    /**   
     * @Groups({"schedule:item", "reservation:item"})
     */
    public function getStatusText(): ?string
    {
        return Order::STATUSES[$this->status];
    }

    /**
     * @return Collection|BathReservationItems[]
     */
    public function getAdditionalServiceItems(): Collection
    {
        return $this->additionalServiceItems;
    }

    public function addAdditionalServiceItem(BathReservationItems $additionalServiceItem): self
    {
        if (!$this->additionalServiceItems->contains($additionalServiceItem)) {
            $this->additionalServiceItems[] = $additionalServiceItem;
            $additionalServiceItem->setBathReservation($this);
        }

        return $this;
    }

    public function removeAdditionalServiceItem(BathReservationItems $additionalServiceItem): self
    {
        if ($this->additionalServiceItems->removeElement($additionalServiceItem)) {
            // set the owning side to null (unless already changed)
            if ($additionalServiceItem->getBathReservation() === $this) {
                $additionalServiceItem->setBathReservation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|BathPayment[]
     */
    public function getBathPayments(): Collection
    {
        return $this->bathPayments;
    }

    public function addBathPayment(BathPayment $bathPayment): self
    {
        if (!$this->bathPayments->contains($bathPayment)) {
            $this->bathPayments[] = $bathPayment;
            $bathPayment->setBathReservation($this);
        }

        return $this;
    }

    public function removeBathPayment(BathPayment $bathPayment): self
    {
        if ($this->bathPayments->removeElement($bathPayment)) {
            // set the owning side to null (unless already changed)
            if ($bathPayment->getBathReservation() === $this) {
                $bathPayment->setBathReservation(null);
            }
        }

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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): self
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(string $customerPhone): self
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    public function getBathName(): ?string
    {
        return $this->bathName;
    }

    public function setBathName(string $bathName): self
    {
        $this->bathName = $bathName;

        return $this;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(\DateTimeImmutable $expiredAt): self
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }
}
