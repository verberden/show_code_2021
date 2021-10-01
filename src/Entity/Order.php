<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @UniqueEntity("hash")
 * @ORM\Table(name="`order`")
 * @property string $statusText
 */
class Order
{
    public const NEW_STATUS = 'new';
    public const PAYED_STATUS = 'payed';
    public const PREPARING_STATUS = 'preparing';
    public const PREPARED_STATUS = 'prepared';
    public const DELIVERING_STATUS = 'delivering';
    public const DELIVERED_STATUS = 'delivered';
    public const CANCELLED_STATUS = 'cancelled';
    public const DECLINED_STATUS = 'declined';
    // public const REFUNDED_PARTIAL_STATUS = 'refunded_partial';
    public const REFUNDED_FULL_STATUS = 'refunded';
    public const PAYMENT_ERROR_STATUS = 'payment_error';

    public const STATUSES = [
        self::NEW_STATUS => 'Новый',
        self::PAYED_STATUS => 'Оплачен',
        self::PREPARING_STATUS => 'Готовится',
        self::PREPARED_STATUS => 'Готов',
        self::DELIVERING_STATUS => 'Доставляется',
        self::DELIVERED_STATUS => 'Доставлен',
        self::CANCELLED_STATUS => 'Отменён',
        self::DECLINED_STATUS => 'Отменён банком/проблема оплаты',
        self::PAYMENT_ERROR_STATUS => 'Ошибка оплаты',
        // self::REFUNDED_PARTIAL_STATUS => 'Частичный возврат оплаты',
        self::REFUNDED_FULL_STATUS  => 'Полный возврат оплаты',
    ];

    public const STATUSES_FOR_BBUTTON = [
        self::PAYED_STATUS => 'Оплачен',
        self::PREPARING_STATUS => 'Готовится',
        self::PREPARED_STATUS => 'Готов',
        self::DELIVERING_STATUS => 'Доставляется',
        self::DELIVERED_STATUS => 'Доставлен',
    ];

    public const STATUS_COLOR = [
        self::NEW_STATUS => 'alert-secondary',
        self::PAYED_STATUS => 'alert-secondary',
        self::PREPARING_STATUS => 'alert-primary',
        self::PREPARED_STATUS => 'alert-brand',
        self::DELIVERING_STATUS => 'alert-info',
        self::DELIVERED_STATUS => 'alert-success',
        self::CANCELLED_STATUS => 'alert-danger',
        self::DECLINED_STATUS => 'alert-danger',
        self::PAYMENT_ERROR_STATUS => 'alert-danger',
        // self::REFUNDED_PARTIAL_STATUS => 'alert-warning',
        self::REFUNDED_FULL_STATUS => 'alert-danger',
    ];

    public const STATUS_BTN_COLOR = [
        self::PREPARING_STATUS => 'btn-primary',
        self::PREPARED_STATUS => 'btn-brand',
        self::DELIVERING_STATUS => 'btn-info',
        self::DELIVERED_STATUS => 'btn-success',
        self::CANCELLED_STATUS => 'btn-danger',
    ];

    public const EDITABLE_STATUSES = [
        self::NEW_STATUS,
        self::PAYED_STATUS,
        self::PREPARING_STATUS,
        self::PREPARED_STATUS,
        self::DELIVERING_STATUS,
    ];

    public const SELECTABLE_STATUSES_TEXT = [
        self::PAYED_STATUS => self::STATUSES[self::PAYED_STATUS ],
        self::PREPARING_STATUS => self::STATUSES[self::PREPARING_STATUS ],
        self::PREPARED_STATUS => self::STATUSES[self::PREPARED_STATUS],
        self::DELIVERING_STATUS => self::STATUSES[self::DELIVERING_STATUS],
    ];
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"order:list", "order:item"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"order:list", "order:item"})
     * @Assert\NotBlank
     */
    private $cost;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank
     * @Groups({"order:list", "order:item"})
     */
    private $deliveryTime;

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
     * @ORM\OneToMany(targetEntity=OrderItem::class, mappedBy="order", cascade={"persist"}, orphanRemoval=true)
     * @Groups({"order:item"})
     * @Assert\NotBlank
     */
    private $orderItems;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"order:list", "order:item"})
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"order:list", "order:item"})
     */
    private $houseText;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="orders", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"order:list", "order:item"})
     */
    private $customer;

    /**
     * @ORM\Column(type="integer", options={"default" : 1})
     * @Groups({"order:list", "order:item"})
     */
    private $numberOfPersons;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders")
     */
    private $deliveryMan;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="order")
     * @Groups({"order:list", "order:item"})
     */
    private $payments;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups({"order:list", "order:item"})
     */
    private $hash;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $notifyToken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"order:list", "order:item"})
     */
    private $customerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"order:list", "order:item"})
     */
    private $customerPhone;

    /**
     * @ORM\Column(type="integer", options={"default" : 0}, nullable=true)
     * @Groups({"order:list", "order:item"})
     */
    private $paymentSum;


    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->hash = bin2hex(random_bytes(12));
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDeliveryTime(): ?\DateTime
    {
        return $this->deliveryTime;
    }

    public function setDeliveryTime(\DateTime $deliveryTime): self
    {
        $this->deliveryTime = $deliveryTime;

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
     * @return Collection|OrderItem[]
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): self
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems[] = $orderItem;
            $orderItem->setOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): self
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

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
     * @Groups({"order:list", "order:item"})
     */
    public function getStatusText(): ?string
    {
        return self::STATUSES[$this->status];
    }

    public function getHouseText(): ?string
    {
        return $this->houseText;
    }

    public function setHouseText(string $houseText): self
    {
        $this->houseText = $houseText;

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

    public function getNumberOfPersons(): ?int
    {
        return $this->numberOfPersons;
    }

    public function setNumberOfPersons(int $numberOfPersons): self
    {
        $this->numberOfPersons = $numberOfPersons;

        return $this;
    }

    public function getDeliveryMan(): ?User
    {
        return $this->deliveryMan;
    }

    public function setDeliveryMan(?User $deliveryMan): self
    {
        $this->deliveryMan = $deliveryMan;

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function isDelivered(): bool
    {
        return $this->status === Order::DELIVERED_STATUS;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getNotifyToken(): ?string
    {
        return $this->notifyToken;
    }

    public function setNotifyToken(string $notifyToken): self
    {
        $this->notifyToken = $notifyToken;

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

    public function getPaymentSum(): ?int
    {
        return $this->paymentSum;
    }

    public function setPaymentSum(int $paymentSum): self
    {
        $this->paymentSum = $paymentSum;

        return $this;
    }

    public function getFactOrderItemsCost(): int
    {
        $items = $this->getOrderItems();
        $cost = 0;
        foreach($items as $item) {
            $cost += $item->getCost();
        }

        return $cost;
    }

}
