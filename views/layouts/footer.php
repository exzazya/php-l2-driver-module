<?php
$companyName  = defined('COMPANY_NAME') ? COMPANY_NAME : 'Jetlouge Travels';
$companyEmail = defined('COMPANY_EMAIL') ? COMPANY_EMAIL : 'logistic2jetlougetravels@gmail.com';
$companyPhone = defined('COMPANY_PHONE') ? COMPANY_PHONE : '+63 900 000 0000';
?>
<footer class="app-footer mt-4">
  <div class="container py-3 small text-muted d-flex flex-column flex-md-row align-items-center justify-content-between">
    <div>
      &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>. All rights reserved.
    </div>
    <div class="mt-2 mt-md-0">
      Contact:
      <a href="mailto:<?php echo htmlspecialchars($companyEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($companyEmail, ENT_QUOTES, 'UTF-8'); ?></a>
      &bull;
      <a href="tel:<?php echo preg_replace('/[^0-9+]/','',$companyPhone); ?>"><?php echo htmlspecialchars($companyPhone, ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
    <div class="mt-2 mt-md-0">
      <a href="<?php echo route('legal.policies'); ?>#terms" target="_blank" rel="noopener">Terms</a>
      &middot;
      <a href="<?php echo route('legal.policies'); ?>#privacy" target="_blank" rel="noopener">Privacy</a>
    </div>
  </div>
</footer>
<style>
  .app-footer { background: #fff; border-top: 1px solid #eee; }
  /* When desktop sidebar is visible, shift footer to align with main content */
  @media (min-width: 768px) {
    #main-content + .app-footer { margin-left: var(--sidebar-width); }
    #main-content.expanded + .app-footer { margin-left: 0; }
  }
</style>
