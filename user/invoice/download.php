<?php
/**
 * Generates the invoice as a downloadable PDF using FPDF.
 * No output may be sent before FPDF writes the file, so this page
 * deliberately does not include the site header or footer.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';
require_once __DIR__ . '/invoice_data.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/fpdf/fpdf.php';

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

// FPDF's core fonts only support Latin-1, so strip anything outside it
function pdfText($text) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $text);
}

/**
 * Draws the Epsilon logo on the PDF.
 *
 * FPDF cannot place an SVG and has no arc command, so the mark is rebuilt
 * from primitives: a vertical bar, a horizontal bar, and the two facing
 * elliptical arcs approximated by a series of short straight lines.
 *
 * $cx, $cy is the centre of the mark and $size is its full width in mm.
 */
function pdfLogo($pdf, $cx, $cy, $size) {
    $u = $size / 100;            // one unit of the 100x100 logo grid, in mm

    // off-white tile behind the mark, matching assets/images/logo.svg
    $pdf->SetFillColor(228, 218, 197);
    $pdf->Rect($cx - 55 * $u, $cy - 55 * $u, 110 * $u, 110 * $u, 'F');

    $pdf->SetDrawColor(31, 35, 40);   // charcoal

    // the two bars
    $pdf->SetLineWidth(8 * $u);
    $pdf->Line($cx, $cy - 47 * $u, $cx, $cy + 47 * $u);   // vertical
    $pdf->Line($cx - 45 * $u, $cy, $cx + 45 * $u, $cy);   // horizontal

    // the facing arcs: half ellipses rx = 18, ry = 22.7 in grid units
    $rx = 18 * $u;
    $ry = 22.7 * $u;
    $steps = 28;

    foreach ([-1, 1] as $side) {
        // centre of each arc sits 28.5 units out from the middle
        $ax = $cx + $side * 28.5 * $u;
        $prevX = $prevY = null;

        for ($i = 0; $i <= $steps; $i++) {
            $angle = -M_PI / 2 + (M_PI * $i / $steps);     // -90deg .. +90deg
            $x = $ax - $side * $rx * cos($angle);          // bulge toward centre
            $y = $cy + $ry * sin($angle);

            if ($prevX !== null) {
                $pdf->Line($prevX, $prevY, $x, $y);
            }
            $prevX = $x;
            $prevY = $y;
        }
    }

    $pdf->SetLineWidth(0.2);      // back to the FPDF default
    $pdf->SetDrawColor(0, 0, 0);
}

$pdf = new FPDF();
$pdf->AddPage();

// ---------- Header ----------
pdfLogo($pdf, 15.5, 15.5, 11);

$pdf->SetXY(24, 10);
$pdf->SetFont('Helvetica', 'B', 20);
$pdf->SetTextColor(31, 35, 40);
$pdf->Cell(106, 11, pdfText('Epsilon'), 0, 0);

$pdf->SetFont('Helvetica', 'B', 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 11, pdfText('INVOICE'), 0, 1, 'R');

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(110, 110, 110);
$pdf->Cell(120, 5, pdfText('University Student Marketplace'), 0, 0);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(0, 5, pdfText($invoice['invoice_number']), 0, 1, 'R');

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(110, 110, 110);
$pdf->Cell(120, 5, '', 0, 0);
$pdf->Cell(0, 5, pdfText('Issued: ' . date('d M Y', strtotime($invoice['generated_at']))), 0, 1, 'R');

$pdf->Ln(4);
$pdf->SetDrawColor(210, 210, 210);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(6);

// ---------- Buyer and Seller ----------
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(95, 6, pdfText('BILLED TO (BUYER)'), 0, 0);
$pdf->Cell(0, 6, pdfText('SOLD BY (SELLER)'), 0, 1);

$buyerLines = [
    $txn['buyer_name'],
    'Student ID: ' . $txn['buyer_student_id'],
    $txn['buyer_department'],
    $txn['buyer_email'],
    $txn['buyer_phone'],
];
$sellerLines = [
    $txn['seller_name'],
    'Student ID: ' . $txn['seller_student_id'],
    $txn['seller_department'],
    $txn['seller_email'],
    $txn['seller_phone'],
];

for ($i = 0; $i < count($buyerLines); $i++) {
    $pdf->SetFont('Helvetica', $i === 0 ? 'B' : '', 9);
    $pdf->Cell(95, 5, pdfText($buyerLines[$i]), 0, 0);
    $pdf->Cell(0, 5, pdfText($sellerLines[$i]), 0, 1);
}

$pdf->Ln(4);

/*
 * Only one address belongs on the invoice. A pickup point means the buyer
 * went and collected the item, so printing a delivery address as well would
 * contradict it. Without one, the seller brought it to the buyer.
 */
if ($pickup) {
    $heading = 'COLLECTED FROM';
    $lines = array_merge(
        [$txn['seller_name'] . ' - ' . $txn['seller_phone']],
        preg_split('/\r\n|\r|\n/', $pickup)
    );
} elseif ($address) {
    $heading = 'DELIVERED TO';
    $lines = [
        $address['receiver_name'] . ' - ' . $address['phone'],
        $address['full_address'],
        $address['area'] . ', ' . $address['district'],
    ];
} else {
    $heading = null;
    $lines = [];
}

if ($heading) {
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(0, 6, pdfText($heading), 0, 1);
    $pdf->SetFont('Helvetica', '', 9);
    foreach ($lines as $line) {
        $pdf->Cell(0, 5, pdfText($line), 0, 1);
    }
    $pdf->Ln(3);
}

// ---------- Item table ----------
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetFillColor(240, 242, 245);
$pdf->Cell(85, 8, pdfText(' Description'), 1, 0, 'L', true);
$pdf->Cell(35, 8, pdfText('Category'),     1, 0, 'C', true);
$pdf->Cell(30, 8, pdfText('Condition'),    1, 0, 'C', true);
$pdf->Cell(40, 8, pdfText('Amount'),       1, 1, 'R', true);

$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(85, 8, pdfText(' ' . $txn['title']), 1, 0, 'L');
$pdf->Cell(35, 8, pdfText($txn['category_name']), 1, 0, 'C');
$pdf->Cell(30, 8, pdfText($txn['condition']), 1, 0, 'C');
$pdf->Cell(40, 8, pdfText('Tk ' . number_format($txn['bid_amount'], 2) . ' '), 1, 1, 'R');

$pdf->SetTextColor(110, 110, 110);
$pdf->Cell(150, 7, pdfText('Original Asking Price '), 0, 0, 'R');
$pdf->Cell(40, 7, pdfText('Tk ' . number_format($txn['price'], 2) . ' '), 0, 1, 'R');

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(150, 9, pdfText('TOTAL PAID '), 0, 0, 'R');
$pdf->SetTextColor(47, 111, 237);
$pdf->Cell(40, 9, pdfText('Tk ' . number_format($invoice['total_amount'], 2) . ' '), 0, 1, 'R');

$pdf->Ln(4);

// ---------- Payment details ----------
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(0, 6, pdfText('PAYMENT DETAILS'), 0, 1);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(60, 5, pdfText('Transaction No: #' . $txn['transaction_id']), 0, 0);
$pdf->Cell(65, 5, pdfText('Payment Method: ' . $txn['payment_method']), 0, 0);
$pdf->Cell(0, 5, pdfText('Status: ' . $txn['payment_status']), 0, 1);
$pdf->Cell(0, 5, pdfText('Transaction Date: ' . date('d M Y, h:i A', strtotime($txn['transaction_date']))), 0, 1);

$pdf->Ln(8);
$pdf->SetFont('Helvetica', 'I', 8);
$pdf->SetTextColor(140, 140, 140);
$pdf->Cell(0, 5, pdfText('This is a computer-generated invoice from Epsilon and does not require a signature.'), 0, 1, 'C');

// 'D' tells the browser to download the file rather than display it
$pdf->Output('D', $invoice['invoice_number'] . '.pdf');
