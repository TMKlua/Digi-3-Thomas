<?php

namespace App\Entity;

use App\Repository\InvoiceHeaderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceHeaderRepository::class)]
class InvoiceHeader
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 35)]
    private ?string $invoiceHeaderNumber = null;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $invoiceHeaderType = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $invoiceHeaderDate = null;

    #[ORM\Column]
    private ?int $invoiceHeaderCustomer = null;

    #[ORM\Column]
    private ?int $invoiceHeaderHT = null;

    #[ORM\Column]
    private ?int $invoiceHeaderVAT = null;

    #[ORM\Column]
    private ?int $invoiceHeaderTTC = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceHeaderUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceHeaderNumber(): ?string
    {
        return $this->invoiceHeaderNumber;
    }

    public function setInvoiceHeaderNumber(string $invoiceHeaderNumber): static
    {
        $this->invoiceHeaderNumber = $invoiceHeaderNumber;

        return $this;
    }

    public function getInvoiceHeaderType(): ?string
    {
        return $this->invoiceHeaderType;
    }

    public function setInvoiceHeaderType(?string $invoiceHeaderType): static
    {
        $this->invoiceHeaderType = $invoiceHeaderType;

        return $this;
    }

    public function getInvoiceHeaderDate(): ?\DateTimeInterface
    {
        return $this->invoiceHeaderDate;
    }

    public function setInvoiceHeaderDate(?\DateTimeInterface $invoiceHeaderDate): static
    {
        $this->invoiceHeaderDate = $invoiceHeaderDate;

        return $this;
    }

    public function getInvoiceHeaderCustomer(): ?int
    {
        return $this->invoiceHeaderCustomer;
    }

    public function setInvoiceHeaderCustomer(?int $invoiceHeaderCustomer): static
    {
        $this->invoiceHeaderCustomer = $invoiceHeaderCustomer;

        return $this;
    }

    public function getInvoiceHeaderHT(): ?int
    {
        return $this->invoiceHeaderHT;
    }

    public function setInvoiceHeaderHT(?int $invoiceHeaderHT): static
    {
        $this->invoiceHeaderHT = $invoiceHeaderHT;

        return $this;
    }

    public function getInvoiceHeaderVAT(): ?int
    {
        return $this->invoiceHeaderVAT;
    }

    public function setInvoiceHeaderVAT(int $invoiceHeaderVAT): static
    {
        $this->invoiceHeaderVAT = $invoiceHeaderVAT;

        return $this;
    }

    public function getInvoiceHeaderTTC(): ?int
    {
        return $this->invoiceHeaderTTC;
    }

    public function setInvoiceHeaderTTC(int $invoiceHeaderTTC): static
    {
        $this->invoiceHeaderTTC = $invoiceHeaderTTC;

        return $this;
    }

    public function getInvoiceHeaderUrl(): ?string
    {
        return $this->invoiceHeaderUrl;
    }

    public function setInvoiceHeaderUrl(?string $invoiceHeaderUrl): static
    {
        $this->invoiceHeaderUrl = $invoiceHeaderUrl;

        return $this;
    }
}
