<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use App\Service\EncryptionService;
use Doctrine\ORM\Mapping as ORM;
use Fhp\Model\SEPAAccount;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $bic = null;

    #[ORM\Column(length: 255)]
    private ?string $bankCode = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tanMediaName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tanMechanism = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $iban = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(string $bic): static
    {
        $this->bic = $bic;

        return $this;
    }

    public function getBankCode(): ?string
    {
        return $this->bankCode;
    }

    public function setBankCode(string $bankCode): static
    {
        $this->bankCode = $bankCode;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getTanMediaName(EncryptionService $crypto): ?string
    {
        return $crypto->decrypt($this->tanMediaName);
    }

    public function setTanMediaName(string $plainTanMediaName, EncryptionService $crypto): static
    {
        $this->tanMediaName = $crypto->encrypt($plainTanMediaName);

        return $this;
    }

    public function getTanMechanism(EncryptionService $crypto): ?string
    {
        return $crypto->decrypt($this->tanMechanism);
    }

    public function setTanMechanism(string $plainTanMechanism, EncryptionService $crypto): static
    {
        $this->tanMechanism = $crypto->encrypt($plainTanMechanism);

        return $this;
    }

    public function getUsername(EncryptionService $crypto): ?string
    {
        return $crypto->decrypt($this->username);
    }

    public function setUsername(string $plainUsername, EncryptionService $crypto): static
    {
        $this->username = $crypto->encrypt($plainUsername);

        return $this;
    }

    public function getPassword(EncryptionService $crypto): ?string
    {
        return $crypto->decrypt($this->password);
    }

    public function setPassword(string $plainPassword, EncryptionService $crypto): static
    {
        $this->password = $crypto->encrypt($plainPassword);

        return $this;
    }

    public function getIban(EncryptionService $crypto): ?string
    {
        return $crypto->decrypt($this->iban);
    }

    public function setIban(string $plainIban, EncryptionService $crypto): static
    {
        $this->iban = $crypto->encrypt($plainIban);

        return $this;
    }

    public function getAccountNumber(EncryptionService $crypto): ?string
    {
        return $crypto->decrypt($this->accountNumber);
    }

    public function setAccountNumber(string $plainAccountNumber, EncryptionService $crypto): static
    {
        $this->accountNumber = $crypto->encrypt($plainAccountNumber);

        return $this;
    }

    public function getSEPAAcount(EncryptionService $crypto)
    {
        $sepaAccount = new SEPAAccount();

        $sepaAccount
            ->setIban($this->getIban($crypto))
            ->setBic($this->getBic())
            ->setBlz($this->getBankCode())
            ->setAccountNumber($this->getAccountNumber($crypto))
        ;

        return $sepaAccount;
    }
}
