<?php namespace professionalweb\payment\interfaces;

/**
 * Interface for CNP Processing GmbH service
 * @package professionalweb\payment\interfaces
 */
interface CnpService
{
    const PAYMENT_CNP = 'cnp';

    /**
     * Approve transaction by id
     *
     * @param string $id
     *
     * @return bool
     */
    public function approveTransaction($id);

    /**
     * Get transaction status
     *
     * @param string $id
     *
     * @return string
     */
    public function getTransactionStatus($id);
}