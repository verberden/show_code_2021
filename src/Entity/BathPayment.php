<?php

namespace App\Entity;

use App\Repository\BathPaymentRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\Entity(repositoryClass=BathPaymentRepository::class)
 */
class BathPayment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"reservation:item"})
     */
    private $amount;

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
     * @ORM\Column(type="string", length=255)
     */
    private $systemId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"reservation:item"})
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"reservation:item"})
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"reservation:item"})
     */
    private $processingSystem;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $paymentSystem;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $bankInfo = [];

    /**
     * @ORM\ManyToOne(targetEntity=BathReservation::class, inversedBy="bathPayments")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $bathReservation;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSystemId(): ?string
    {
        return $this->systemId;
    }

    public function setSystemId(string $systemId): self
    {
        $this->systemId = $systemId;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
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

    public function getProcessingSystem(): ?string
    {
        return $this->processingSystem;
    }

    public function setProcessingSystem(string $processingSystem): self
    {
        $this->processingSystem = $processingSystem;

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

    public function getBankInfo(): ?array
    {
        return $this->bankInfo;
    }

    public function setBankInfo(?array $bankInfo): self
    {
        $this->bankInfo = $bankInfo;

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
}
