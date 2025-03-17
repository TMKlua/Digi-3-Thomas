<?php

namespace App\Entity;

use App\Repository\CustomersRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomersRepository::class)]
class Customers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $customerName = null;

    #[ORM\Column(length: 255)]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 255)]
    private ?string $customerPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerAddressStreet = null;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $customerAddressZipcode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerAddressCity = null;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $customerAddressCountry = null;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $customerVAT = null;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $customerSIREN = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerReference = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $customerDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $customerDateTo = null;

    #[ORM\Column]
    private ?int $customerUserMaj = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): static
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    public function getCustomerAddressStreet(): ?string
    {
        return $this->customerAddressStreet;
    }

    public function setCustomerAddressStreet(string $customerAddressStreet): static
    {
        $this->customerAddressStreet = $customerAddressStreet;

        return $this;
    }

    public function getCustomerAddressZipcode(): ?string
    {
        return $this->customerAddressZipcode;
    }

    public function setCustomerAddressZipcode(string $customerAddressZipcode): static
    {
        $this->customerAddressZipcode = $customerAddressZipcode;

        return $this;
    }

    public function getCustomerAddressCity(): ?string
    {
        return $this->customerAddressCity;
    }

    public function setCustomerAddressCity(?string $customerAddressCity): static
    {
        $this->customerAddressCity = $customerAddressCity;

        return $this;
    }

    public function getCustomerAddressCountry(): ?string
    {
        return $this->customerAddressCountry;
    }

    public function setCustomerAddressCountry(?string $customerAddressCountry): static
    {
        $this->customerAddressCountry = $customerAddressCountry;

        return $this;
    }

    public function getCustomerVAT(): ?string
    {
        return $this->customerVAT;
    }

    public function setCustomerVAT(?string $customerVAT): static
    {
        $this->customerVAT = $customerVAT;

        return $this;
    }

    public function getCustomerSIREN(): ?string
    {
        return $this->customerSIREN;
    }

    public function setCustomerSIREN(?string $customerSIREN): static
    {
        $this->customerSIREN = $customerSIREN;

        return $this;
    }

    public function getCustomerReference(): ?string
    {
        return $this->customerReference;
    }

    public function setCustomerReference(?string $customerReference): static
    {
        $this->customerReference = $customerReference;

        return $this;
    }

    public function getCustomerDateFrom(): ?\DateTimeInterface
    {
        return $this->customerDateFrom;
    }

    public function setCustomerDateFrom(?\DateTimeInterface $customerDateFrom): static
    {
        $this->customerDateFrom = $customerDateFrom;

        return $this;
    }

    public function getCustomerDateTo(): ?\DateTimeInterface
    {
        return $this->customerDateTo;
    }

    public function setCustomerDateTo(?\DateTimeInterface $customerDateTo): static
    {
        $this->customerDateTo = $customerDateTo;

        return $this;
    }

    public function getCustomerUserMaj(): ?int
    {
        return $this->customerUserMaj;
    }

    public function setCustomerUserMaj(int $customerUserMaj): static
    {
        $this->customerUserMaj = $customerUserMaj;

        return $this;
    }
}
