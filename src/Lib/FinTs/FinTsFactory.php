<?php

namespace App\Lib\FinTs;

use Fhp\FinTs;
use Fhp\Options\Credentials;
use Fhp\Options\FinTsOptions;

/**
 * Framework-agnostic factory for building a FinTs instance. Mirrors phpFinTS's
 * Samples/init.php — pure construction from explicit configuration values, with no
 * dependency on Symfony's request stack, parameter bag, or any encryption/decryption
 * concern. Callers are responsible for resolving those values themselves before
 * calling create().
 */
final class FinTsFactory
{
    private function __construct()
    {
        // Not instantiable — this class only exposes static construction.
    }

    /**
     * @param string      $server            the URL where the bank server can be reached
     * @param string      $bankCode          the bank code (Bankleitzahl) of the bank
     * @param string      $username          the FinTS/HBCI username
     * @param string      $pin               the FinTS/HBCI PIN (not the bank card PIN)
     * @param string      $productName       the FinTS registration number for your product
     * @param string      $productVersion    an arbitrary version string for your product
     * @param string|null $persistedInstance a previously FinTs::persist()-ed instance, to resume
     *                                       a suspended dialog (e.g. across two HTTP requests).
     *                                       Leave null to start a fresh dialog.
     */
    public static function create(
        string $server,
        string $bankCode,
        string $username,
        string $pin,
        string $productName,
        string $productVersion,
        ?string $persistedInstance = null,
    ): FinTs {
        $options = new FinTsOptions();
        $options->url = $server;
        $options->bankCode = $bankCode;
        $options->productName = $productName;
        $options->productVersion = $productVersion;

        $credentials = Credentials::create($username, $pin);

        return FinTs::new($options, $credentials, $persistedInstance);
    }
}