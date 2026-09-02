<?php
/**
 * Builds database/ER_Diagram.pdf - the Entity Relationship diagram for Epsilon
 * drawn in classic Chen notation.
 *
 * Run it from the command line whenever the schema changes:
 *     C:\xampp\php\php.exe database\generate_erd.php
 *
 * Notation used:
 *   rectangle        entity (a table)
 *   ellipse          attribute (a column)
 *   underlined text  primary key
 *   dashed underline foreign key
 *   double ellipse   unique / candidate key
 *   diamond          relationship
 *   1, N             how many rows take part on each side
 */

require_once __DIR__ . '/../includes/fpdf/fpdf.php';

/**
 * FPDF has no ellipse or diamond of its own, so this subclass adds them.
 * Extending the class is what gives access to _out(), the method that writes
 * raw drawing commands into the PDF.
 */
class ErdPdf extends FPDF
{
    // Brand colours, kept in one place
    const INK    = [31, 35, 40];
    const CREAM  = [228, 218, 197];
    const MUTED  = [120, 120, 120];
    const LINE   = [90, 90, 90];

    /** Ellipse drawn from four bezier curves. $style: D outline, F fill, DF both */
    public function Ellipse($cx, $cy, $rx, $ry, $style = 'D')
    {
        $op = ($style === 'F') ? 'f' : (($style === 'FD' || $style === 'DF') ? 'B' : 'S');

        $k  = $this->k;
        $h  = $this->h;
        $lx = 4 / 3 * (M_SQRT2 - 1) * $rx;
        $ly = 4 / 3 * (M_SQRT2 - 1) * $ry;

        $this->_out(sprintf('%.2F %.2F m', ($cx + $rx) * $k, ($h - $cy) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($cx + $rx) * $k, ($h - ($cy - $ly)) * $k,
            ($cx + $lx) * $k, ($h - ($cy - $ry)) * $k,
            $cx * $k,         ($h - ($cy - $ry)) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($cx - $lx) * $k, ($h - ($cy - $ry)) * $k,
            ($cx - $rx) * $k, ($h - ($cy - $ly)) * $k,
            ($cx - $rx) * $k, ($h - $cy) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($cx - $rx) * $k, ($h - ($cy + $ly)) * $k,
            ($cx - $lx) * $k, ($h - ($cy + $ry)) * $k,
            $cx * $k,         ($h - ($cy + $ry)) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($cx + $lx) * $k, ($h - ($cy + $ry)) * $k,
            ($cx + $rx) * $k, ($h - ($cy + $ly)) * $k,
            ($cx + $rx) * $k, ($h - $cy) * $k));
        $this->_out($op);
    }

    /** Diamond used for a relationship */
    public function Diamond($cx, $cy, $w, $h, $style = 'D')
    {
        $op = ($style === 'F') ? 'f' : (($style === 'FD' || $style === 'DF') ? 'B' : 'S');
        $k  = $this->k;
        $ph = $this->h;

        $this->_out(sprintf('%.2F %.2F m', $cx * $k, ($ph - ($cy - $h / 2)) * $k));
        $this->_out(sprintf('%.2F %.2F l', ($cx + $w / 2) * $k, ($ph - $cy) * $k));
        $this->_out(sprintf('%.2F %.2F l', $cx * $k, ($ph - ($cy + $h / 2)) * $k));
        $this->_out(sprintf('%.2F %.2F l', ($cx - $w / 2) * $k, ($ph - $cy) * $k));
        $this->_out('h');
        $this->_out($op);
    }

    /** Text centred on a point */
    public function TextAt($cx, $cy, $txt, $size = 7, $style = '', $rgb = self::INK)
    {
        $this->SetFont('Helvetica', $style, $size);
        $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
        $w = $this->GetStringWidth($txt);
        $this->SetXY($cx - $w / 2 - 1, $cy - $size / 2.6);
        $this->Cell($w + 2, $size / 1.9, $txt, 0, 0, 'C');
    }

    /** Entity: a labelled rectangle */
    public function Entity($cx, $cy, $w, $h, $name)
    {
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(...self::INK);
        $this->SetFillColor(...self::CREAM);
        $this->Rect($cx - $w / 2, $cy - $h / 2, $w, $h, 'FD');
        $this->TextAt($cx, $cy, $name, 8.5, 'B');
    }

    /**
     * Attribute ellipse. $flags may contain:
     *   pk     underline the name and draw it slightly bolder
     *   uk     draw a second outline (unique / candidate key)
     *   fk     italic label - the column points at another table
     */
    public function Attribute($cx, $cy, $label, $flags = '')
    {
        $isPk = str_contains($flags, 'pk');
        $isUk = str_contains($flags, 'uk');
        $isFk = str_contains($flags, 'fk');

        $style = $isPk ? 'BU' : ($isFk ? 'I' : '');

        $this->SetFont('Helvetica', $style, 6.5);
        $rx = max(11, $this->GetStringWidth($label) / 2 + 3.2);
        $ry = 4.6;

        $this->SetLineWidth($isPk ? 0.45 : 0.3);
        $this->SetDrawColor(...($isFk ? self::LINE : self::INK));
        $this->SetFillColor(255, 255, 255);
        $this->Ellipse($cx, $cy, $rx, $ry, 'FD');
        if ($isUk) {
            $this->SetLineWidth(0.25);
            $this->Ellipse($cx, $cy, $rx - 1.1, $ry - 1.1, 'D');
        }

        $this->TextAt($cx, $cy, $label, 6.5, $style, $isFk ? self::LINE : self::INK);

        // A primary key gets a solid underline (drawn by the 'U' font style).
        // A foreign key gets a dashed one, which is the usual way to tell them apart.
        if ($isFk) {
            $this->SetFont('Helvetica', 'I', 6.5);
            $tw = $this->GetStringWidth($label);
            $this->SetDrawColor(...self::LINE);
            $this->SetLineWidth(0.25);
            $this->_out('[1.1 0.9] 0 d');
            $this->Line($cx - $tw / 2, $cy + 2.5, $cx + $tw / 2, $cy + 2.5);
            $this->_out('[] 0 d');
        }

        return [$rx, $ry];
    }

    /** Relationship diamond with its label */
    public function Relationship($cx, $cy, $label, $w = 30, $h = 15)
    {
        $this->SetLineWidth(0.45);
        $this->SetDrawColor(...self::INK);
        $this->SetFillColor(246, 244, 239);
        $this->Diamond($cx, $cy, $w, $h, 'FD');

        // Split a two word label over two lines so it fits inside the shape
        $parts = explode(' ', $label);
        if (count($parts) > 1) {
            $this->TextAt($cx, $cy - 2.2, $parts[0], 6.5);
            $this->TextAt($cx, $cy + 2.2, implode(' ', array_slice($parts, 1)), 6.5);
        } else {
            $this->TextAt($cx, $cy, $label, 6.5);
        }
    }

    /** Plain connector */
    public function Connect($x1, $y1, $x2, $y2, $width = 0.3)
    {
        $this->SetLineWidth($width);
        $this->SetDrawColor(...self::LINE);
        $this->Line($x1, $y1, $x2, $y2);
    }

    /** Cardinality marker (1 or N) placed on a connector */
    public function Card($x, $y, $txt)
    {
        $this->TextAt($x, $y, $txt, 7.5, 'B');
    }

    /** Page heading */
    public function Heading($title, $subtitle = '')
    {
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetTextColor(...self::INK);
        $this->SetXY(12, 10);
        $this->Cell(0, 6, $title, 0, 1);

        if ($subtitle !== '') {
            $this->SetFont('Helvetica', '', 8.5);
            $this->SetTextColor(...self::MUTED);
            $this->SetX(12);
            $this->Cell(0, 4.5, $subtitle, 0, 1);
        }

        $this->SetDrawColor(210, 205, 195);
        $this->SetLineWidth(0.3);
        $this->Line(12, 22, $this->GetPageWidth() - 12, 22);
    }

    /** Footer on every page */
    public function Footer()
    {
        $this->SetY(-12);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(...self::MUTED);
        $this->Cell(0, 5, 'Epsilon  -  Second-Hand Book/Gadget Exchange for Students', 0, 0, 'L');
        $this->Cell(0, 5, 'Page ' . $this->PageNo(), 0, 0, 'R');
    }
}

$pdf = new ErdPdf('L', 'mm', 'A4');
$pdf->SetAutoPageBreak(false);
$pdf->SetTitle('Epsilon - Entity Relationship Diagram');
$pdf->SetAuthor('Epsilon');

$W = $pdf->GetPageWidth();   // 297
$H = $pdf->GetPageHeight();  // 210

/* =====================================================================
   PAGE 1 - Cover
   ===================================================================== */
$pdf->AddPage();

$pdf->SetFillColor(...ErdPdf::INK);
$pdf->Rect(0, 0, $W, 62, 'F');

// logo tile
$pdf->SetFillColor(...ErdPdf::CREAM);
$pdf->Rect($W / 2 - 9, 16, 18, 18, 'F');
$pdf->SetDrawColor(...ErdPdf::INK);
$pdf->SetLineWidth(1.3);
$pdf->Line($W / 2, 18.5, $W / 2, 31.5);
$pdf->Line($W / 2 - 6.5, 25, $W / 2 + 6.5, 25);

$pdf->SetFont('Helvetica', 'B', 26);
$pdf->SetTextColor(242, 236, 224);
$pdf->SetXY(0, 38);
$pdf->Cell($W, 12, 'Epsilon', 0, 1, 'C');

$pdf->SetFont('Helvetica', 'B', 17);
$pdf->SetTextColor(...ErdPdf::INK);
$pdf->SetXY(0, 78);
$pdf->Cell($W, 9, 'Entity Relationship Diagram', 0, 1, 'C');

$pdf->SetFont('Helvetica', '', 10.5);
$pdf->SetTextColor(...ErdPdf::MUTED);
$pdf->SetXY(0, 89);
$pdf->Cell($W, 6, 'Second-Hand Book / Gadget Exchange for Students', 0, 1, 'C');

$pdf->SetFont('Helvetica', '', 9.5);
$pdf->SetXY(0, 104);
$pdf->Cell($W, 5, 'Database: epsilon_db      14 entities      Normalised to Third Normal Form (3NF)', 0, 1, 'C');

// contents
$items = [
    '2.  Notation used in this diagram',
    '3.  Overview - all entities and how they relate',
    '4.  Accounts, categories and listings',
    '5.  Bidding and transactions',
    '6.  Payment, delivery and invoice',
    '7.  Wishlist, reviews, reports and notifications',
];
$pdf->SetFont('Helvetica', 'B', 9.5);
$pdf->SetTextColor(...ErdPdf::INK);
$pdf->SetXY(0, 126);
$pdf->Cell($W, 5, 'Contents', 0, 1, 'C');

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(...ErdPdf::MUTED);
$y = 134;
foreach ($items as $it) {
    $pdf->SetXY(0, $y);
    $pdf->Cell($W, 5, $it, 0, 1, 'C');
    $y += 5.4;
}

$pdf->SetFont('Helvetica', '', 8);
$pdf->SetXY(0, $H - 24);
$pdf->Cell($W, 5, 'Generated ' . date('d F Y'), 0, 1, 'C');

/* =====================================================================
   PAGE 2 - Notation legend
   ===================================================================== */
$pdf->AddPage();
$pdf->Heading('Notation', 'The diagram uses Chen notation. Every shape below means the same thing on every page.');

$legend = [
    ['entity',   'Entity',            'A table in the database.'],
    ['attr',     'Attribute',         'A column of that table.'],
    ['pk',       'Primary key',       'SOLID underline, bold black. Identifies one row uniquely.'],
    ['fk',       'Foreign key',       'DASHED underline, italic grey. Points at another table.'],
    ['uk',       'Unique key',        'Double outline. No two rows may repeat this value.'],
    ['rel',      'Relationship',      'How two entities are linked.'],
    ['card',     'Cardinality',       '1 means one row, N means many rows.'],
];

$y = 38;
foreach ($legend as [$kind, $title, $desc]) {
    $cx = 48;

    switch ($kind) {
        case 'entity':
            $pdf->Entity($cx, $y, 34, 13, 'Product');
            break;
        case 'attr':
            $pdf->Attribute($cx, $y, 'title');
            break;
        case 'pk':
            $pdf->Attribute($cx, $y, 'product_id', 'pk');
            break;
        case 'uk':
            $pdf->Attribute($cx, $y, 'email', 'uk');
            break;
        case 'fk':
            $pdf->Attribute($cx, $y, 'seller_id', 'fk');
            break;
        case 'rel':
            $pdf->Relationship($cx, $y, 'posts');
            break;
        case 'card':
            $pdf->Connect($cx - 15, $y, $cx + 15, $y, 0.4);
            $pdf->Card($cx - 11, $y - 3.6, '1');
            $pdf->Card($cx + 11, $y - 3.6, 'N');
            break;
    }

    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(...ErdPdf::INK);
    $pdf->SetXY(78, $y - 4.5);
    $pdf->Cell(45, 5, $title, 0, 0);

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(...ErdPdf::MUTED);
    $pdf->SetXY(124, $y - 4.5);
    $pdf->Cell(0, 5, $desc, 0, 0);

    $y += 21;
}

/* Primary key next to foreign key, so the two underlines can be compared directly.
   This is the pair most often mixed up. */
$pdf->SetFont('Helvetica', 'B', 9.5);
$pdf->SetTextColor(...ErdPdf::INK);
$pdf->SetXY(178, 34);
$pdf->Cell(0, 5, 'Telling a key apart at a glance', 0, 1);

$pdf->SetDrawColor(225, 221, 213);
$pdf->SetLineWidth(0.25);
$pdf->Rect(176, 40, 106, 46);

$pdf->Attribute(202, 52, 'notification_id', 'pk');
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(...ErdPdf::INK);
$pdf->SetXY(228, 48.5);
$pdf->Cell(0, 5, 'solid line = primary key', 0, 0);

$pdf->Attribute(202, 72, 'product_id', 'fk');
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(...ErdPdf::INK);
$pdf->SetXY(228, 68.5);
$pdf->Cell(0, 5, 'dashed line = foreign key', 0, 0);

$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(...ErdPdf::MUTED);
$pdf->SetXY(178, 92);
$pdf->MultiCell(104, 4.4,
    "Both examples come from the Notification table. It has exactly one primary key, "
  . "notification_id. product_id and transaction_id are foreign keys: they may be empty, "
  . "and they record which item the notification is about so that clicking it opens the "
  . "right page.");

$pdf->SetFont('Helvetica', '', 8.5);
$pdf->SetTextColor(...ErdPdf::MUTED);
$pdf->SetXY(12, $H - 32);
$pdf->MultiCell($W - 24, 4.6,
    "Strict Chen notation leaves foreign keys out, because the relationship diamond already carries "
  . "that meaning. They are drawn here anyway - in italic grey with a dashed underline - so that every "
  . "column in the database appears somewhere in this document. The last page lists all 23 of them in one table.");

/* =====================================================================
   PAGE 3 - Overview: every entity and how they relate
   ===================================================================== */

/*
   The page reads left to right: the student on the left, then the things that
   join a student to a product, then the product, then the order it turns into.
   [centre x, centre y, box width]
*/
$E = [
    'Notification'    => [30,  40,  34],
    'User'            => [30,  105, 34],

    'Wishlist'        => [88,  36,  34],
    'Report'          => [88,  68,  34],
    'Bid'             => [88,  142, 34],
    'Review'          => [88,  174, 34],

    'Category'        => [150, 36,  34],
    'Product'         => [150, 105, 34],
    'ProductImage'    => [150, 174, 34],

    'Transaction'     => [208, 105, 34],

    'Payment'         => [268, 42,  34],
    'Invoice'         => [268, 84,  34],
    'Delivery'        => [268, 126, 34],
    'DeliveryAddress' => [268, 168, 34],
];

/**
 * One relationship: two straight segments meeting at the diamond, with a
 * cardinality marker near each entity.
 * $from / $to are entity names, $d is where the diamond goes.
 */
$rels = [
    // [from, to, diamond x, diamond y, label, card near from, card near to]
    ['User',        'Notification',    30,  72,    'notified',    '1', 'N'],

    ['User',        'Wishlist',        59,  70.5,  'saves',       '1', 'N'],
    ['Wishlist',    'Product',         119, 70.5,  'for',         'N', '1'],
    ['User',        'Report',          59,  86.5,  'files',       '1', 'N'],
    ['Report',      'Product',         119, 86.5,  'about',       'N', '1'],
    ['User',        'Product',         90,  105,   'posts',       '1', 'N'],
    ['User',        'Bid',             59,  123.5, 'places',      '1', 'N'],
    ['Bid',         'Product',         119, 123.5, 'receives',    'N', '1'],
    ['User',        'Review',          59,  139.5, 'writes / rated', '1', 'N'],

    ['Category',    'Product',         150, 70,    'classifies',  '1', 'N'],
    ['Product',     'ProductImage',    124, 145,   'has',         '1', 'N'],
    ['Product',     'Transaction',     179, 105,   'sold in',     '1', 'N'],
    ['Bid',         'Transaction',     150, 130,   'leads to',    '1', '1'],
    ['Review',      'Transaction',     180, 131,   'rated by',    '1', '1'],

    // the right-hand column is narrow, so these four diamonds are slimmer
    ['Transaction', 'Payment',         238, 73.5,  'paid by',     '1', '1', 16],
    ['Transaction', 'Invoice',         238, 94.5,  'billed by',   '1', '1', 16],
    ['Transaction', 'Delivery',        238, 115.5, 'handed over', '1', '1', 16],
    ['Transaction', 'DeliveryAddress', 238, 136.5, 'ships to',    '1', '1', 16],
];

$pdf->AddPage();
$pdf->Heading('Overview - all 14 entities',
    'Attributes are left out here so the relationships stay readable. Every entity is drawn in full on the pages that follow.');

// 1. connector lines first, so the shapes drawn afterwards sit on top of them
foreach ($rels as [$from, $to, $dx, $dy, , , ]) {
    $pdf->Connect($E[$from][0], $E[$from][1], $dx, $dy);
    $pdf->Connect($dx, $dy, $E[$to][0], $E[$to][1]);
}

// the buyer / seller link takes the long way round so it crosses nothing
$pdf->Connect(30, 110.5, 30, 187);
$pdf->Connect(30, 187, 208, 187);
$pdf->Connect(208, 187, 208, 110.5);

// 2. relationship diamonds
foreach ($rels as $rel) {
    $pdf->Relationship($rel[2], $rel[3], $rel[4], $rel[7] ?? 20, 13);
}
$pdf->Relationship(120, 187, 'buys / sells', 32, 13);

// 3. entity boxes, drawn last so any line passing behind one is hidden
foreach ($E as $name => [$ex, $ey, $ew]) {
    $pdf->Entity($ex, $ey, $ew, 11, $name);
}

/**
 * Works out where a cardinality marker goes: just clear of the entity box,
 * nudged sideways so it sits beside the connector rather than on top of it.
 */
function cardPos($ex, $ey, $ew, $dx, $dy)
{
    $ux = $dx - $ex;
    $uy = $dy - $ey;
    $len = sqrt($ux * $ux + $uy * $uy);
    $ux /= $len;
    $uy /= $len;

    // how far along the line the edge of the (slightly padded) box is
    $t = PHP_FLOAT_MAX;
    if (abs($ux) > 1e-6) $t = min($t, ($ew / 2 + 4.0) / abs($ux));
    if (abs($uy) > 1e-6) $t = min($t, 11.5 / abs($uy));

    // step sideways off the connector: above it, or to its left when vertical
    $px = -$uy;
    $py = $ux;
    if ($py > 0 || (abs($py) < 1e-6 && $px > 0)) { $px = -$px; $py = -$py; }

    return [$ex + $ux * $t + $px * 4.0, $ey + $uy * $t + $py * 4.0];
}

// 4. cardinality markers, sitting just outside each entity box
foreach ($rels as [$from, $to, $dx, $dy, , $c1, $c2]) {
    [$p1x, $p1y] = cardPos($E[$from][0], $E[$from][1], $E[$from][2], $dx, $dy);
    [$p2x, $p2y] = cardPos($E[$to][0], $E[$to][1], $E[$to][2], $dx, $dy);
    $pdf->Card($p1x, $p1y, $c1);
    $pdf->Card($p2x, $p2y, $c2);
}
// the two ends of the long buyer / seller route
$pdf->Card(34, 118, '1');
$pdf->Card(212, 118, 'N');

$pdf->SetFont('Helvetica', '', 7.5);
$pdf->SetTextColor(...ErdPdf::MUTED);
$pdf->SetXY(14, 196);
$pdf->Cell(0, 4,
    'Two diamonds carry two roles each: Transaction joins User as buyer and as seller, and Review joins User as the writer and as the seller being rated. '
  . 'Notification has two further optional links (product_id, transaction_id) shown as attributes on its own page. All 23 foreign keys are listed on the last page.',
    0, 0, 'L');

/* =====================================================================
   PAGES 4 to 10 - every entity in full Chen notation

   Each entity is listed as [name, caption, [attributes]] where an
   attribute is [label, flags]. Flags: pk = primary key, uk = unique key.
   ===================================================================== */
$entities = [
    'User' => [
        'One student account. Also holds the admin.',
        'Referenced by Product, Bid, Transaction, Wishlist, Review, Report and Notification.',
        [
            ['user_id', 'pk'], ['student_id', 'uk'], ['full_name', ''], ['department', ''],
            ['batch', ''], ['email', 'uk'], ['phone', ''], ['password', ''],
            ['role', ''], ['status', ''], ['created_at', ''],
        ],
    ],
    'Category' => [
        'A listing category such as Books or Laptop.',
        'Kept in its own table so a category can be renamed in one place. It has no foreign keys.',
        [['category_id', 'pk'], ['category_name', 'uk']],
    ],
    'Product' => [
        'One item put up for sale.',
        'seller_id says who is selling it, category_id which category it belongs to.',
        [
            ['product_id', 'pk'], ['seller_id', 'fk'], ['category_id', 'fk'], ['title', ''],
            ['description', ''], ['price', ''], ['condition', ''], ['status', ''],
            ['created_at', ''], ['updated_at', ''],
        ],
    ],
    'ProductImage' => [
        'One photo of a product. A product may have several.',
        'Kept in its own table so the number of photos is not fixed by the schema.',
        [['image_id', 'pk'], ['product_id', 'fk'], ['image_path', ''], ['uploaded_at', '']],
    ],
    'Bid' => [
        'An offer a buyer makes on a product.',
        'counter_amount holds the price the seller offers back.',
        [
            ['bid_id', 'pk'], ['product_id', 'fk'], ['buyer_id', 'fk'],
            ['bid_amount', ''], ['counter_amount', ''], ['status', ''], ['created_at', ''],
        ],
    ],
    'Transaction' => [
        'Created the moment a bid is accepted.',
        'bid_id is unique, so one bid can produce only one order. buyer_id and seller_id are both User.',
        [
            ['transaction_id', 'pk'], ['product_id', 'fk'], ['bid_id', 'fk uk'],
            ['buyer_id', 'fk'], ['seller_id', 'fk'], ['transaction_date', ''], ['status', ''],
        ],
    ],
    'Payment' => [
        'How the order was paid for.',
        'transaction_id is unique - one payment per order.',
        [
            ['payment_id', 'pk'], ['transaction_id', 'fk uk'], ['payment_method', ''],
            ['amount', ''], ['payment_status', ''], ['paid_at', ''], ['created_at', ''],
        ],
    ],
    'Invoice' => [
        'The receipt produced once an order is paid.',
        'One invoice per order, with its own printable invoice number.',
        [
            ['invoice_id', 'pk'], ['transaction_id', 'fk uk'], ['invoice_number', 'uk'],
            ['total_amount', ''], ['generated_at', ''],
        ],
    ],
    'Delivery' => [
        'Tracks the handover of the item.',
        'pickup_address is filled in by the seller for cash on delivery.',
        [
            ['delivery_id', 'pk'], ['transaction_id', 'fk uk'], ['tracking_number', 'uk'],
            ['pickup_address', ''], ['delivery_status', ''], ['updated_at', ''],
        ],
    ],
    'DeliveryAddress' => [
        'Where the buyer wants the item sent.',
        'Split into district, area and street so addresses stay searchable.',
        [
            ['address_id', 'pk'], ['transaction_id', 'fk uk'], ['receiver_name', ''],
            ['phone', ''], ['district', ''], ['area', ''], ['full_address', ''],
        ],
    ],
    'Wishlist' => [
        'A product a student saved for later.',
        'Two foreign keys and nothing else - it exists only to join User to Product.',
        [['wishlist_id', 'pk'], ['user_id', 'fk'], ['product_id', 'fk'], ['created_at', '']],
    ],
    'Review' => [
        'The buyer rates the seller after delivery.',
        'transaction_id is unique, so an order can be reviewed only once. buyer_id wrote it, seller_id is rated by it.',
        [
            ['review_id', 'pk'], ['transaction_id', 'fk uk'], ['buyer_id', 'fk'],
            ['seller_id', 'fk'], ['rating', ''], ['comment', ''], ['created_at', ''],
        ],
    ],
    'Report' => [
        'A complaint raised against a listing.',
        'reported_by is the student who raised it.',
        [
            ['report_id', 'pk'], ['product_id', 'fk'], ['reported_by', 'fk'],
            ['reason', ''], ['description', ''], ['status', ''], ['created_at', ''],
        ],
    ],
    'Notification' => [
        'A message shown in the bell menu.',
        'product_id and transaction_id may be empty; when set, clicking the notification opens that item.',
        [
            ['notification_id', 'pk'], ['user_id', 'fk'], ['product_id', 'fk'],
            ['transaction_id', 'fk'], ['type', ''], ['message', ''], ['is_read', ''], ['created_at', ''],
        ],
    ],
];

/** Draws one entity with its attributes fanned out around it in a ring */
function drawChen(ErdPdf $pdf, $cx, $cy, $name, array $attrs, $caption, $note)
{
    $n = count($attrs);

    // A small entity gets a tighter ring so the page never looks empty in the middle
    if ($n <= 3)      { $rx = 38; $ry = 30; }
    elseif ($n <= 6)  { $rx = 46; $ry = 40; }
    else              { $rx = 54; $ry = 50; }

    $boxW = 40;
    $boxH = 13;

    // Attributes are spaced evenly round the ring, starting straight above
    for ($i = 0; $i < $n; $i++) {
        $angle = -M_PI / 2 + ($i * 2 * M_PI / $n);
        $ax = $cx + cos($angle) * $rx;
        $ay = $cy + sin($angle) * $ry;

        // start the line at the edge of the box, not its centre
        $sx = $cx + cos($angle) * min($boxW / 2 + 1, $rx);
        $sy = $cy + sin($angle) * min($boxH / 2 + 1, $ry);

        $pdf->Connect($sx, $sy, $ax, $ay);
        $pdf->Attribute($ax, $ay, $attrs[$i][0], $attrs[$i][1]);
    }

    $pdf->Entity($cx, $cy, $boxW, $boxH, $name);

    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(...ErdPdf::INK);
    $pdf->SetXY($cx - 62, 172);
    $pdf->MultiCell(124, 4.6, $caption, 0, 'C');

    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(...ErdPdf::MUTED);
    $pdf->SetXY($cx - 62, $pdf->GetY() + 0.5);
    $pdf->MultiCell(124, 4, $note, 0, 'C');
}

// Two entities per page, side by side
$pages = [
    ['Accounts and categories',      ['User', 'Category']],
    ['Listings',                     ['Product', 'ProductImage']],
    ['Bidding and orders',           ['Bid', 'Transaction']],
    ['Money',                        ['Payment', 'Invoice']],
    ['Getting the item to the buyer',['Delivery', 'DeliveryAddress']],
    ['Saving and rating',            ['Wishlist', 'Review']],
    ['Moderation and alerts',        ['Report', 'Notification']],
];

foreach ($pages as [$title, $pair]) {
    $pdf->AddPage();
    $pdf->Heading($title, implode('   |   ', $pair)
        . '   -   primary key = solid underline (black),  foreign key = dashed underline (grey italic),  unique key = double outline');

    $centres = [80, 217];
    foreach ($pair as $i => $name) {
        [$caption, $note, $attrs] = $entities[$name];
        drawChen($pdf, $centres[$i], 108, $name, $attrs, $caption, $note);
    }

    // a light divider between the two halves
    $pdf->SetDrawColor(225, 221, 213);
    $pdf->SetLineWidth(0.2);
    $pdf->Line(148.5, 32, 148.5, 165);
}

/* =====================================================================
   LAST PAGE - every foreign key in one table

   This is the page to check the diagram against. Each row is one FOREIGN KEY
   constraint in database.sql, so the count here and the count in the schema
   must agree.
   ===================================================================== */
$fks = [
    // [child table, column, parent table, relationship on the overview, type]
    ['Bid',             'product_id',     'Product',     'receives',       '1:N'],
    ['Bid',             'buyer_id',       'User',        'places',         '1:N'],
    ['Delivery',        'transaction_id', 'Transaction', 'handed over',    '1:1'],
    ['DeliveryAddress', 'transaction_id', 'Transaction', 'ships to',       '1:1'],
    ['Invoice',         'transaction_id', 'Transaction', 'billed by',      '1:1'],
    ['Notification',    'user_id',        'User',        'notified',       '1:N'],
    ['Notification',    'product_id',     'Product',     'about (may be empty)',     '1:N'],
    ['Notification',    'transaction_id', 'Transaction', 'about (may be empty)',     '1:N'],
    ['Payment',         'transaction_id', 'Transaction', 'paid by',        '1:1'],
    ['Product',         'seller_id',      'User',        'posts',          '1:N'],
    ['Product',         'category_id',    'Category',    'classifies',     '1:N'],
    ['ProductImage',    'product_id',     'Product',     'has',            '1:N'],
    ['Report',          'product_id',     'Product',     'about',          '1:N'],
    ['Report',          'reported_by',    'User',        'files',          '1:N'],
    ['Review',          'transaction_id', 'Transaction', 'rated by',       '1:1'],
    ['Review',          'buyer_id',       'User',        'writes',         '1:N'],
    ['Review',          'seller_id',      'User',        'rated',          '1:N'],
    ['Transaction',     'product_id',     'Product',     'sold in',        '1:N'],
    ['Transaction',     'bid_id',         'Bid',         'leads to',       '1:1'],
    ['Transaction',     'buyer_id',       'User',        'buys',           '1:N'],
    ['Transaction',     'seller_id',      'User',        'sells',          '1:N'],
    ['Wishlist',        'user_id',        'User',        'saves',          '1:N'],
    ['Wishlist',        'product_id',     'Product',     'for',            '1:N'],
];

$pdf->AddPage();
$pdf->Heading('All ' . count($fks) . ' foreign keys',
    'One row per FOREIGN KEY in database.sql. Use this page to check the diagram against the schema.');

$colW = [46, 38, 44, 50, 18];
$x0   = ($W - array_sum($colW)) / 2;
$rowH = 6.4;
$y    = 32;

// header row
$pdf->SetFont('Helvetica', 'B', 8.5);
$pdf->SetTextColor(...ErdPdf::INK);
$pdf->SetFillColor(...ErdPdf::CREAM);
$pdf->SetDrawColor(200, 194, 182);
$pdf->SetLineWidth(0.2);
$pdf->SetXY($x0, $y);
foreach (['Table', 'Column', 'Points at', 'Relationship', 'Type'] as $i => $h) {
    $pdf->Cell($colW[$i], $rowH + 1, '  ' . $h, 1, 0, 'L', true);
}
$y += $rowH + 1;

$pdf->SetFont('Helvetica', '', 8);
$lastTable = '';
foreach ($fks as $row) {
    // a faint tint on alternate tables makes the groups easy to scan
    $shade = ($row[0] !== $lastTable);
    $lastTable = $row[0];

    $pdf->SetXY($x0, $y);
    $pdf->SetFillColor(250, 249, 246);
    for ($i = 0; $i < 5; $i++) {
        $pdf->SetFont('Helvetica', $i === 1 ? 'I' : '', 8);
        $pdf->SetTextColor(...($i === 1 ? ErdPdf::LINE : ErdPdf::INK));
        $pdf->Cell($colW[$i], $rowH, '  ' . $row[$i], 1, 0, 'L', $shade);
    }
    $y += $rowH;
}

$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(...ErdPdf::MUTED);
$pdf->SetXY($x0, $y + 4);
$pdf->MultiCell(array_sum($colW), 4.4,
    "Type 1:1 means the foreign key column also carries a UNIQUE index, so the parent row can have at "
  . "most one child. Type 1:N means it does not, so one parent row can have many children.\n"
  . "User appears as the parent of nine of these, which is why it sits on the left of the overview.");

$pdf->Output('F', __DIR__ . '/ER_Diagram.pdf');
echo "written: " . __DIR__ . "/ER_Diagram.pdf\n";

