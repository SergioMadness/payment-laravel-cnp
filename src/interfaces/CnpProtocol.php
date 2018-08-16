<?php namespace professionalweb\payment\interfaces;

use professionalweb\payment\contracts\PayProtocol;

/**
 * Interface for CNP protocol
 * @package professionalweb\payment\interfaces
 */
interface CnpProtocol extends PayProtocol
{
    /**
     * Get transaction status
     *
     * @param string $id
     *
     * @return string
     */
    public function getTransactionStatus($id);

    /**
     * Approve transaction by id
     *
     * @param string $id
     *
     * @return bool
     */
    public function approveTransaction($id);
}