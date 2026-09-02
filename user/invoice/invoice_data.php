<?php
/**
 * Loads every piece of information that appears on an invoice.
 * Shared by the printable HTML view and the PDF download so both
 * always show exactly the same data.
 *
 * Returns null if the invoice does not exist or the user is not
 * the buyer/seller on it.
 */
function getInvoiceData($conn, $transactionId, $userId) {
    $txn = getTransactionForUser($conn, $transactionId, $userId);
    if (!$txn) {
        return null;
    }

    $invoice = fetchOne($conn, "SELECT * FROM Invoice WHERE transaction_id = ?", 'i', [$transactionId]);
    if (!$invoice) {
        return null;
    }

    $address = fetchOne($conn, "SELECT * FROM DeliveryAddress WHERE transaction_id = ?", 'i', [$transactionId]);

    // The seller's pickup point, shown next to the buyer's delivery address
    $delivery = fetchOne($conn, "SELECT pickup_address FROM Delivery WHERE transaction_id = ?", 'i', [$transactionId]);

    return [
        'invoice'     => $invoice,
        'transaction' => $txn,
        'address'     => $address,
        'pickup'      => $delivery['pickup_address'] ?? null,
    ];
}
