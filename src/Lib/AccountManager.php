<?php

namespace App\Lib;

use App\Entity\Account;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Fhp\Model\SEPAAccount;

final class AccountManager
{
    public function __construct(
        private readonly EncryptionService $encryptionService,
        private readonly EntityManagerInterface $entityManager,
    ) {

    }

    public function saveStep1(array $data): Account
    {
        $account = new Account();
        $account->setName($data['name']);
        $account->setBic($data['bic']);
        $account->setBankCode($data['bankCode']);
        $account->setUrl($data['url']);
        $account->setUsername($data['username'], $this->encryptionService);
        $account->setPassword($data['password'], $this->encryptionService);

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        return $account;
    }

    public function saveStep2(array $data, Account $account): void
    {
        $account->setTanMechanism($data['tanMechanism'], $this->encryptionService);
        $account->setTanMediaName(trim($data['tanMediaName']), $this->encryptionService);

        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    public function finalize(SEPAAccount $data, Account $account): void
    {
        $account->setIban($data->getIban(), $this->encryptionService);
        $account->setAccountNumber($data->getAccountNumber(), $this->encryptionService);

        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }
}
