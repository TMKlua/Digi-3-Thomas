<?php

namespace App\Entity;

use App\Repository\InvoiceDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceDetailsRepository::class)]
class InvoiceDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 35)]
    private ?string $invoiceDetailsNumber = null;

    #[ORM\Column(length: 35)]
    private ?string $invoiceDetailsTasks = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $invoiceHeaderDate = null;

    #[ORM\Column]
    private ?int $invoiceHeaderCustomer = null;

    #[ORM\Column]
    private ?int $invoiceDetailsHT = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceDetailsNumber(): ?string
    {
        return $this->invoiceDetailsNumber;
    }

    public function setInvoiceDetailsNumber(string $invoiceDetailsNumber): static
    {
        $this->invoiceDetailsNumber = $invoiceDetailsNumber;

        return $this;
    }

    public function getInvoiceDetailsTasks(): ?string
    {
        return $this->invoiceDetailsTasks;
    }

    public function setInvoiceDetailsTasks(string $invoiceDetailsTasks): static
    {
        $this->invoiceDetailsTasks = $invoiceDetailsTasks;

        return $this;
    }

    public function getInvoiceHeaderDate(): ?\DateTimeInterface
    {
        return $this->invoiceHeaderDate;
    }

    public function setInvoiceHeaderDate(\DateTimeInterface $invoiceHeaderDate): static
    {
        $this->invoiceHeaderDate = $invoiceHeaderDate;

        return $this;
    }

    public function getInvoiceHeaderCustomer(): ?int
    {
        return $this->invoiceHeaderCustomer;
    }

    public function setInvoiceHeaderCustomer(int $invoiceHeaderCustomer): static
    {
        $this->invoiceHeaderCustomer = $invoiceHeaderCustomer;

        return $this;
    }

    public function getInvoiceDetailsHT(): ?int
    {
        return $this->invoiceDetailsHT;
    }

    public function setInvoiceDetailsHT(int $invoiceDetailsHT): static
    {
        $this->invoiceDetailsHT = $invoiceDetailsHT;

        return $this;
    }
}
