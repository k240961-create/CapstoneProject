  </main>

  <!-- ========================================== -->
  <!-- 1. PRE-FOOTER (Perks & VIP Community Club) -->
  <!-- ========================================== -->
  <section class="pre-footer" aria-labelledby="pre-footer-heading">
    <div class="pre-footer__inner">
      <h2 id="pre-footer-heading" class="sr-only">Store Advantages and Member Club</h2>

      <!-- Perks Grid -->
      <div class="pre-footer__perks-grid">
        <div class="pre-footer-card">
          <div class="pre-footer-icon">
            <i data-lucide="truck" class="icon-md"></i>
          </div>
          <div class="pre-footer-text">
            <h4>Free Same-Day Delivery</h4>
            <p>On grocery orders of $60 or more placed before 2:00 PM</p>
          </div>
        </div>

        <div class="pre-footer-card">
          <div class="pre-footer-icon">
            <i data-lucide="leaf" class="icon-md"></i>
          </div>
          <div class="pre-footer-text">
            <h4>100% Farm Fresh Guarantee</h4>
            <p>Hand-picked daily from Australian regional family growers</p>
          </div>
        </div>

        <div class="pre-footer-card">
          <div class="pre-footer-icon">
            <i data-lucide="shield-check" class="icon-md"></i>
          </div>
          <div class="pre-footer-text">
            <h4>Temperature-Controlled Vans</h4>
            <p>Chilled transit ensures dairy, meat &amp; seafood arrive fresh</p>
          </div>
        </div>

        <div class="pre-footer-card">
          <div class="pre-footer-icon">
            <i data-lucide="headphones" class="icon-md"></i>
          </div>
          <div class="pre-footer-text">
            <h4>7-Day Support Assistance</h4>
            <p>Friendly local customer care ready to help anytime</p>
          </div>
        </div>
      </div>

      <!-- Newsletter Bar -->
      <div class="pre-footer__newsletter">
        <div class="newsletter-copy">
          <span class="newsletter-pill">✨ VIP Member Club</span>
          <h3 class="newsletter-title">Join the Maxi Fresh Club</h3>
          <p class="newsletter-desc">Get <strong>$10 off</strong> your first order, seasonal farm recipes, and exclusive member-only weekly specials.</p>
        </div>
        <form class="newsletter-form" onsubmit="event.preventDefault(); showToast('🎉 Welcome! Your $10 voucher code is FRESH10'); this.reset();">
          <label class="sr-only" for="pre-newsletter-email">Email Address</label>
          <input id="pre-newsletter-email" type="email" placeholder="Enter your email address" required class="newsletter-input">
          <button type="submit" class="btn-tomato newsletter-btn">
            <span>Subscribe</span>
            <i data-lucide="arrow-right" class="icon-sm"></i>
          </button>
        </form>
      </div>
    </div>
  </section>

  <!-- ========================================== -->
  <!-- 2. MAIN FOOTER (Navigation, Hours, Contact)-->
  <!-- ========================================== -->
  <footer class="site-footer">
    <div class="site-footer__inner">
      <!-- Col 1: Brand & Socials -->
      <div class="footer-col footer-col--brand">
        <a href="<?= BASE_URL ?>/index.php" class="wordmark wordmark--footer">Maxi Fine Foods</a>
        <p class="site-footer__blurb">Your neighbourhood online grocer delivering hand-selected farm produce, artisan bakeries, prime cuts, and sustainable dairy straight to your doorstep.</p>
        <div class="footer-social-links">
          <a href="#" aria-label="Facebook" class="social-icon-btn"><i data-lucide="facebook" class="icon-sm"></i></a>
          <a href="#" aria-label="Instagram" class="social-icon-btn"><i data-lucide="instagram" class="icon-sm"></i></a>
          <a href="#" aria-label="Twitter / X" class="social-icon-btn"><i data-lucide="twitter" class="icon-sm"></i></a>
          <a href="#" aria-label="YouTube" class="social-icon-btn"><i data-lucide="youtube" class="icon-sm"></i></a>
        </div>
        <div class="footer-cert-tags">
          <span class="cert-tag"><i data-lucide="check-circle-2" class="icon-xs"></i> 100% Australian Owned &amp; Operated</span>
          <span class="cert-tag"><i data-lucide="check-circle-2" class="icon-xs"></i> Certified Organic &amp; Sustainable Partner</span>
        </div>
      </div>

      <!-- Col 2: Departments -->
      <div class="footer-col">
        <h4 class="footer-heading">Market Departments</h4>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🔥</span> Weekly Specials</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🍎</span> Fresh Produce</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🥖</span> Artisan Bakery</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🥩</span> Meat &amp; Seafood</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🧀</span> Dairy &amp; Farm Eggs</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🧃</span> Cold Drinks &amp; Pantry</a></li>
        </ul>
      </div>

      <!-- Col 3: Customer Care -->
      <div class="footer-col">
        <h4 class="footer-heading">Customer Care</h4>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>/my_orders.php"><i data-lucide="package" class="icon-xs"></i> Track My Orders</a></li>
          <li><a href="<?= BASE_URL ?>/cart.php"><i data-lucide="shopping-bag" class="icon-xs"></i> Shopping Basket</a></li>
          <li><a href="<?= BASE_URL ?>/login.php"><i data-lucide="user" class="icon-xs"></i> My Account Login</a></li>
          <li><a href="<?= BASE_URL ?>/register.php"><i data-lucide="user-plus" class="icon-xs"></i> Create Account</a></li>
          <li><a href="<?= BASE_URL ?>/faq.php"><i data-lucide="help-circle" class="icon-xs"></i> Help &amp; FAQs</a></li>
          <li><a href="<?= BASE_URL ?>/refunds.php"><i data-lucide="refresh-cw" class="icon-xs"></i> Return &amp; Refund Policy</a></li>
          <li><a href="<?= BASE_URL ?>/delivery_terms.php"><i data-lucide="truck" class="icon-xs"></i> Delivery Zones &amp; Rates</a></li>
        </ul>
      </div>

      <!-- Col 4: Opening & Delivery Hours -->
      <div class="footer-col">
        <h4 class="footer-heading">Opening &amp; Delivery Hours</h4>
        <div class="footer-hours-card">
          <div class="hours-row">
            <span class="hours-day">Monday &ndash; Friday</span>
            <span class="hours-time">7:00 AM &ndash; 9:00 PM</span>
          </div>
          <div class="hours-row">
            <span class="hours-day">Saturday</span>
            <span class="hours-time">8:00 AM &ndash; 9:00 PM</span>
          </div>
          <div class="hours-row">
            <span class="hours-day">Sunday &amp; Holidays</span>
            <span class="hours-time">8:00 AM &ndash; 7:00 PM</span>
          </div>
          <div class="hours-cutoff-note">
            <i data-lucide="clock" class="icon-xs"></i>
            <span><strong>Same-Day Cutoff:</strong> Order by 2:00 PM</span>
          </div>
        </div>
      </div>

      <!-- Col 5: Location & Contact -->
      <div class="footer-col">
        <h4 class="footer-heading">Depot &amp; Contact</h4>
        <ul class="footer-contact-list">
          <li>
            <i data-lucide="map-pin" class="icon-sm footer-contact-icon"></i>
            <div>
              <strong>Central Market Depot:</strong>
              <p>42 Market Street, Sydney NSW 2000</p>
            </div>
          </li>
          <li>
            <i data-lucide="phone" class="icon-sm footer-contact-icon"></i>
            <div>
              <strong>Support Hotline:</strong>
              <p><a href="tel:1800629436">1800 629 436</a> (Toll-Free)</p>
            </div>
          </li>
          <li>
            <i data-lucide="mail" class="icon-sm footer-contact-icon"></i>
            <div>
              <strong>Email:</strong>
              <p><a href="mailto:support@maxifinefoods.com.au">support@maxifinefoods.com.au</a></p>
            </div>
          </li>
        </ul>
        <div class="footer-live-status">
          <span class="status-pulse-dot"></span>
          <span>Online Store is <strong>Open &amp; Dispatching</strong></span>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- 3. SUB-FOOTER (Copyright, Legal, Payments) -->
    <!-- ========================================== -->
    <div class="site-sub-footer">
      <div class="site-sub-footer__inner">
        <div class="sub-footer-legal">
          <p class="sub-footer-copy">&copy; <?= date('Y') ?> Maxi Fine Foods Pty Ltd. ABN 84 192 847 291. All rights reserved.</p>
          <div class="sub-footer-links">
            <a href="<?= BASE_URL ?>/privacy.php">Privacy Policy</a>
            <span class="divider-dot">&bull;</span>
            <a href="<?= BASE_URL ?>/terms.php">Terms of Service</a>
            <span class="divider-dot">&bull;</span>
            <a href="<?= BASE_URL ?>/security.php">Security &amp; Encryption</a>
            <span class="divider-dot">&bull;</span>
            <a href="<?= BASE_URL ?>/delivery_terms.php">Delivery Terms</a>
          </div>
        </div>
        <div class="sub-footer-payments">
          <span class="payments-title">Accepted Payment Methods:</span>
          <div class="payments-grid">
            <span class="pay-badge" title="Visa">💳 Visa</span>
            <span class="pay-badge" title="Mastercard">💳 Mastercard</span>
            <span class="pay-badge" title="American Express">💳 AMEX</span>
            <span class="pay-badge" title="Apple Pay">🍎 Apple Pay</span>
            <span class="pay-badge" title="Cash on Delivery">💵 Cash on Delivery</span>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <?php if (in_array($currentRole ?? 'guest', ['guest', 'customer'], true)): ?>
    <div class="store-assistant" data-store-assistant>
      <section class="store-assistant__panel" data-assistant-panel id="store-assistant-panel" role="dialog" aria-labelledby="store-assistant-title" hidden>
        <header class="store-assistant__header">
          <div>
            <h2 id="store-assistant-title">Maxi Assistant</h2>
            <p data-assistant-status>AI replies when configured; quick store answers otherwise</p>
          </div>
          <button type="button" class="store-assistant__close" data-assistant-close aria-label="Close assistant">
            <i data-lucide="x" class="icon-sm"></i>
          </button>
        </header>
        <div class="store-assistant__messages" data-assistant-messages role="log" aria-live="polite" aria-relevant="additions text">
          <p class="store-assistant__message store-assistant__message--assistant">Hi! Ask me about products, delivery, payments, returns, or where to track an order.</p>
        </div>
        <form class="store-assistant__form" data-assistant-form>
          <label class="sr-only" for="store-assistant-input">Message Maxi Assistant</label>
          <textarea id="store-assistant-input" data-assistant-input rows="2" maxlength="800" placeholder="Ask a question" required></textarea>
          <button type="submit" class="btn-tomato" aria-label="Send message">
            <i data-lucide="arrow-up" class="icon-sm"></i>
          </button>
        </form>
        <p class="store-assistant__privacy">When AI is configured, your message is sent to our AI provider. Never include personal, password, or card details.</p>
      </section>
      <button type="button" class="store-assistant__trigger" data-assistant-toggle aria-expanded="false" aria-controls="store-assistant-panel">
        <i data-lucide="message-circle" class="icon-sm"></i>
        <span>Ask Maxi</span>
      </button>
    </div>
  <?php endif; ?>

  <div id="toast" class="toast" aria-live="polite"></div>
</div>

<script>window.MFF_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/assets/main.js?v=<?= time() ?>"></script>
</body>
</html>
