<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';
require_once __DIR__ . '/invoice_data.php';

requireLogin();

$transactionId = filter_input(INPUT_GET, 'transaction_id', FILTER_VALIDATE_INT);
$userId        = (int) $_SESSION['user_id'];

$data = getInvoiceData($conn, $transactionId, $userId);
if (!$data) {
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}

$invoice = $data['invoice'];
$txn     = $data['transaction'];
$address = $data['address'];
$pickup  = $data['pickup'];

$pageTitle = 'Invoice ' . $invoice['invoice_number'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<style>
  /* Hide the site chrome when the invoice is printed */
  @media print {
    nav, footer, .no-print { display: none !important; }
    body { background: #fff; }
    .invoice-box { border: none !important; box-shadow: none !important; }
  }
</style>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-9">

      <!-- Action buttons (never printed) -->
      <div class="d-flex flex-wrap gap-2 mb-3 no-print">
        <button onclick="window.print()" class="btn btn-brand"><i class="bi bi-printer"></i> Print Invoice</button>
        <a href="<?php echo BASE_URL; ?>/user/invoice/download.php?transaction_id=<?php echo $transactionId; ?>"
           class="btn btn-outline-brand"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>
        <a href="<?php echo BASE_URL; ?>/user/transaction/details.php?id=<?php echo $transactionId; ?>"
           class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
      </div>

      <div class="invoice-box bg-white border rounded-3 p-4 p-md-5">

        <!-- Header -->
        <div class="row mb-4">
          <div class="col-7">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
              <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="" width="32" height="32">
              <span class="brand-word">Epsilon</span>
            </h4>
            <p class="text-muted small mb-0">University Student Marketplace</p>
          </div>
          <div class="col-5 text-end">
            <h5 class="fw-bold mb-1">INVOICE</h5>
            <div class="small"><strong><?php echo sanitize($invoice['invoice_number']); ?></strong></div>
            <div class="small text-muted">
              Issued: <?php echo date('d M Y', strtotime($invoice['generated_at'])); ?>
            </div>
          </div>
        </div>

        <hr>

        <!-- Parties -->
        <div class="row g-4 mb-4">
          <div class="col-12 col-sm-6">
            <h6 class="fw-bold text-uppercase small text-muted">Billed To (Buyer)</h6>
            <div class="fw-semibold"><?php echo sanitize($txn['buyer_name']); ?></div>
            <div class="small text-muted">
              Student ID: <?php echo sanitize($txn['buyer_student_id']); ?><br>
              <?php echo sanitize($txn['buyer_department']); ?><br>
              <?php echo sanitize($txn['buyer_email']); ?><br>
              <?php echo sanitize($txn['buyer_phone']); ?>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <h6 class="fw-bold text-uppercase small text-muted">Sold By (Seller)</h6>
            <div class="fw-semibold"><?php echo sanitize($txn['seller_name']); ?></div>
            <div class="small text-muted">
              Student ID: <?php echo sanitize($txn['seller_student_id']); ?><br>
              <?php echo sanitize($txn['seller_department']); ?><br>
              <?php echo sanitize($txn['seller_email']); ?><br>
              <?php echo sanitize($txn['seller_phone']); ?>
            </div>
          </div>
        </div>

        <?php /*
          Only one address belongs here. If the seller named a pickup point the
          buyer went and collected it, so showing a delivery address as well
          would contradict it. Otherwise the seller brought it to the buyer.
        */ ?>
        <?php if ($pickup): ?>
          <div class="mb-4">
            <h6 class="fw-bold text-uppercase small text-muted">Collected From</h6>
            <div class="small">
              <?php echo sanitize($txn['seller_name']); ?> &middot; <?php echo sanitize($txn['seller_phone']); ?><br>
              <span style="white-space:pre-line;"><?php echo sanitize($pickup); ?></span>
            </div>
          </div>
        <?php elseif ($address): ?>
          <div class="mb-4">
            <h6 class="fw-bold text-uppercase small text-muted">Delivered To</h6>
            <div class="small">
              <?php echo sanitize($address['receiver_name']); ?> &middot; <?php echo sanitize($address['phone']); ?><br>
              <?php echo sanitize($address['full_address']); ?>,
              <?php echo sanitize($address['area']); ?>, <?php echo sanitize($address['district']); ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Line items -->
        <div class="table-responsive">
          <table class="table">
            <thead class="table-light">
              <tr><th>Description</th><th>Category</th><th>Condition</th><th class="text-end">Amount</th></tr>
            </thead>
            <tbody>
              <tr>
                <td class="fw-semibold"><?php echo sanitize($txn['title']); ?></td>
                <td><?php echo sanitize($txn['category_name']); ?></td>
                <td><?php echo sanitize($txn['condition']); ?></td>
                <td class="text-end">Tk <?php echo number_format($txn['bid_amount'], 2); ?></td>
              </tr>
              <tr>
                <td colspan="3" class="text-end text-muted">Original Asking Price</td>
                <td class="text-end text-muted">Tk <?php echo number_format($txn['price'], 2); ?></td>
              </tr>
              <tr>
                <td colspan="3" class="text-end fw-bold fs-5">Total Paid</td>
                <td class="text-end fw-bold fs-5 text-primary">Tk <?php echo number_format($invoice['total_amount'], 2); ?></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Payment / transaction meta -->
        <div class="row small mt-3">
          <div class="col-6 col-md-3">
            <div class="text-muted">Transaction No.</div>
            <div class="fw-semibold">#<?php echo $txn['transaction_id']; ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Transaction Date</div>
            <div class="fw-semibold"><?php echo date('d M Y', strtotime($txn['transaction_date'])); ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Payment Method</div>
            <div class="fw-semibold"><?php echo sanitize($txn['payment_method']); ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Payment Status</div>
            <div class="fw-semibold text-success"><?php echo sanitize($txn['payment_status']); ?></div>
          </div>
        </div>

        <hr class="mt-4">
        <p class="text-center text-muted small mb-0">
          This is a computer-generated invoice from Epsilon and does not require a signature.
        </p>

      </div>
    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
