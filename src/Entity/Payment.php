<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=PaymentRepository::class)
 */
class Payment
{
    const WAIT = 'waiting';
    const PAYED = 'payed';
    const PAYMENT_ERROR = 'payment_error';
    const RETURN_PARTIAL = 'return_partial';
    const RETURN_FULL = 'return_full';

    const STATUSES = [
        self::WAIT => 'Ожидание платежа',
        self::PAYED => 'Оплачен',
        self::PAYMENT_ERROR => 'Ошибка при платеже',
        self::RETURN_PARTIAL => 'Частичный возврат',
        self::RETURN_FULL => 'Полный возврат',
    ];
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"order:list", "order:item"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $systemId;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"order:list", "order:item"})
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"order:list", "order:item"})
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $paymentSystem;

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
    private $orderId;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="payments")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $order;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $bankInfo = [];

    /**
     * @ORM\Column(type="string", length=255, options={"default" : "app"})
     * @Groups({"order:list", "order:item"})
     */
    private $processingSystem;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $expiredAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSystemId(): ?string
    {
        return $this->systemId;
    }

    public function setSystemId(string $systemId): self
    {
        $this->systemId = $systemId;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

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

    public function getPaymentSystem(): ?string
    {
        return $this->paymentSystem;
    }

    public function setPaymentSystem(string $paymentSystem): self
    {
        $this->paymentSystem = $paymentSystem;

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

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getBankInfo(): ?array
    {
        return $this->bankInfo;
    }

    public function setBankInfo(?array $bankInfo): self
    {
        $this->bankInfo = $bankInfo;

        return $this;
    }

    public function getProcessingSystem(): ?string
    {
        return $this->processingSystem;
    }

    public function setProcessingSystem(string $processingSystem): self
    {
        $this->processingSystem = $processingSystem;

        return $this;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?\DateTimeImmutable $expiredAt): self
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }
}
