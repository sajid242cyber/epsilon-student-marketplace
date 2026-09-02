<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

$sql = "SELECT * FROM Notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$notifications = mysqli_stmt_get_result($stmt);

// How each notification type is shown in the list
$typeStyles = [
    'New Bid'            => ['icon' => 'bi-hammer',        'color' => 'primary'],
    'Counter Offer'      => ['icon' => 'bi-arrow-left-right', 'color' => 'warning'],
    'Bid Accepted'       => ['icon' => 'bi-check-circle',  'color' => 'success'],
    'Bid Rejected'       => ['icon' => 'bi-x-circle',      'color' => 'danger'],
    'Payment Successful' => ['icon' => 'bi-credit-card',   'color' => 'success'],
    'Delivery Update'    => ['icon' => 'bi-truck',         'color' => 'info'],
];

$pageTitle = 'Notifications';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="bi bi-bell"></i> Notifications</h4>
        <?php if (getUnreadNotificationCount($conn, $userId) > 0): ?>
          <a href="<?php echo BASE_URL; ?>/user/notification/mark_read.php?all=1" class="btn btn-sm btn-outline-brand">
            Mark all as read
          </a>
        <?php endif; ?>
      </div>

      <?php if (mysqli_num_rows($notifications) === 0): ?>
        <div class="empty-state bg-white rounded-3 border">
          <i class="bi bi-bell-slash" style="font-size:2.5rem;"></i>
          <p class="mt-2 mb-0">You have no notifications yet.</p>
        </div>
      <?php else: ?>
        <div class="list-group">
          <?php while ($n = mysqli_fetch_assoc($notifications)):
              $style = $typeStyles[$n['type']] ?? ['icon' => 'bi-info-circle', 'color' => 'secondary'];

              /*
               * Clicking a notification should open whatever it is about.
               * mark_read.php records it as read and then forwards the user
               * on to the product or the transaction.
               */
              $target = BASE_URL . '/user/notification/mark_read.php?id=' . (int) $n['notification_id'];
              $hasTarget = ($n['product_id'] || $n['transaction_id']);
          ?>
            <a href="<?php echo $target; ?>"
               class="list-group-item list-group-item-action d-flex align-items-start gap-3 <?php echo $n['is_read'] ? '' : 'bg-light border-start border-4 border-' . $style['color']; ?>">
              <div class="text-<?php echo $style['color']; ?>" style="font-size:1.4rem;">
                <i class="bi <?php echo $style['icon']; ?>"></i>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <span class="badge bg-<?php echo $style['color']; ?>"><?php echo $n['type']; ?></span>
                  <small class="text-muted text-nowrap"><?php echo time_ago($n['created_at']); ?></small>
                </div>
                <p class="mb-0 mt-1"><?php echo sanitize($n['message']); ?></p>
                <?php if ($hasTarget): ?>
                  <small class="text-muted">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <?php echo $n['transaction_id'] ? 'View transaction' : 'View product'; ?>
                  </small>
                <?php endif; ?>
              </div>
              <?php if (!$n['is_read']): ?>
                <span class="badge rounded-pill bg-<?php echo $style['color']; ?>" title="Unread">&nbsp;</span>
              <?php endif; ?>
            </a>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
