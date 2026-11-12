/**
 * IRONCLAD GAMING SANCTUARY — Main Client Script
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Sticky Navbar Scroll Effect
  const navbar = document.querySelector('.iron-navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 40) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  }

  // 2. Interactive Booking Engine Logic (if on booking page)
  const bookingForm = document.getElementById('ironcladBookingForm');
  if (bookingForm) {
    const stationSelect = document.getElementById('bookingStation');
    const dateInput = document.getElementById('bookingDate');
    const durationSelect = document.getElementById('bookingDuration');
    const slotsContainer = document.getElementById('availableSlotsContainer');
    const basePriceEl = document.getElementById('summaryBasePrice');
    const discountEl = document.getElementById('summaryDiscount');
    const totalAmountEl = document.getElementById('summaryTotalAmount');
    const hiddenTotalInput = document.getElementById('hiddenFinalAmount');
    const promoInput = document.getElementById('promoCodeInput');
    const applyPromoBtn = document.getElementById('applyPromoBtn');
    const promoMessageEl = document.getElementById('promoMessage');
    const custPhoneInput = document.getElementById('customerPhone');
    const custEmailInput = document.getElementById('customerEmail');

    const baseUrl = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : './';

    let currentDiscountType = null;
    let currentDiscountVal = 0;

    // Load available slots
    function loadSlots() {
      const stationId = stationSelect ? stationSelect.value : '';
      const dateVal = dateInput ? dateInput.value : '';
      const durationVal = durationSelect ? parseFloat(durationSelect.value) || 1 : 1;

      if (!slotsContainer) return;

      if (!stationId || !dateVal) {
        slotsContainer.innerHTML = '<div class="text-muted-iron p-3 text-center small"><i class="bi bi-info-circle me-1"></i> Please select a gaming station and date to inspect available start slots.</div>';
        return;
      }

      slotsContainer.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-warning" role="status"></div> <span class="ms-2 text-muted-iron small">Checking arena slot availability...</span></div>';

      const apiUrl = `${baseUrl}api/availability.php?station_id=${encodeURIComponent(stationId)}&date=${encodeURIComponent(dateVal)}&duration=${encodeURIComponent(durationVal)}`;

      fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            slotsContainer.innerHTML = `<div class="alert alert-warning py-2 px-3 small">${data.message || 'Unable to check availability.'}</div>`;
            return;
          }

          if (data.is_closed) {
            slotsContainer.innerHTML = `<div class="alert alert-danger py-2 px-3 small"><i class="bi bi-exclamation-octagon me-1"></i> ${data.message || 'The arena is scheduled closed on this date.'}</div>`;
            return;
          }

          if (!data.slots || data.slots.length === 0) {
            slotsContainer.innerHTML = `<div class="alert alert-warning py-2 px-3 small"><i class="bi bi-calendar-x me-1"></i> No operating slots available for a ${durationVal}-hour session on this date.</div>`;
            return;
          }

          const availableSlots = data.slots.filter(s => s.available);
          let html = '';

          const durText = durationVal < 1 ? `${Math.round(durationVal * 60)} Mins` : `${durationVal} hr${durationVal > 1 ? 's' : ''}`;
          if (availableSlots.length === 0) {
            const currDateObj = new Date(dateVal + 'T00:00:00');
            currDateObj.setDate(currDateObj.getDate() + 1);
            const nextDateStr = currDateObj.toISOString().split('T')[0];
            const nextDateFormatted = currDateObj.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short' });

            html += `
              <div class="alert alert-warning p-3 rounded mb-3 shadow-sm" style="background: rgba(214, 168, 75, 0.12); border: 1.5px solid var(--gold); color: #fff;">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <i class="bi bi-clock-history text-gold fs-5"></i>
                  <strong class="text-gold">All Slots for ${dateVal} are Past or Booked</strong>
                </div>
                <div class="small text-light mb-2">
                  No slots remain for a ${durText} session on this date (Sanctuary operating hours: 10:00 AM – 11:00 PM).
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <button type="button" class="btn btn-warning btn-sm text-dark fw-bold" id="btnQuickTomorrow" data-date="${nextDateStr}">
                    <i class="bi bi-calendar2-check-fill me-1"></i> Switch to Tomorrow (${nextDateFormatted})
                  </button>
                  ${durationVal > 1 ? `
                    <button type="button" class="btn btn-outline-light btn-sm" id="btnQuick1Hour">
                      <i class="bi bi-hourglass-split me-1"></i> Try Shorter 1-Hour Session
                    </button>
                  ` : ''}
                </div>
              </div>
            `;
          } else {
            html += `
              <div class="text-green small mb-2 d-flex align-items-center gap-1">
                <i class="bi bi-check-circle-fill"></i>
                <span><strong>${availableSlots.length} slot${availableSlots.length > 1 ? 's' : ''} available</strong> for ${durText}. Select your start time below:</span>
              </div>
            `;
          }

          html += '<div class="slot-grid">';
          let firstAvailableChecked = false;

          data.slots.forEach(slot => {
            const disabledAttr = slot.available ? '' : 'disabled';
            let statusBadge = '';
            let extraClass = '';

            if (slot.available) {
              statusBadge = '<span class="d-block text-success fw-bold" style="font-size:0.68rem; letter-spacing:0.04em;"><i class="bi bi-check2"></i> FREE</span>';
            } else if (slot.is_past) {
              statusBadge = '<span class="d-block text-secondary" style="font-size:0.65rem;">PAST</span>';
              extraClass = 'slot-past';
            } else {
              statusBadge = '<span class="d-block text-danger fw-bold" style="font-size:0.65rem;"><i class="bi bi-lock-fill"></i> BOOKED</span>';
              extraClass = 'slot-booked';
            }

            const isChecked = (slot.available && !firstAvailableChecked) ? 'checked' : '';
            if (isChecked) {
              firstAvailableChecked = true;
            }

            html += `
              <div class="slot-item ${extraClass}" title="${slot.available ? 'Available Slot' : (slot.is_past ? 'Past Time' : 'Booked')}">
                <input type="radio" name="start_time" id="slot_${slot.time.replace(/:/g, '')}" value="${slot.time}" ${disabledAttr} ${isChecked} required>
                <label class="slot-label ${extraClass}" for="slot_${slot.time.replace(/:/g, '')}">
                  <span class="d-block">${slot.display}</span>
                  ${statusBadge}
                </label>
              </div>
            `;
          });
          html += '</div>';

          slotsContainer.innerHTML = html;

          // Wire Quick Action buttons if rendered
          const tomorrowBtn = document.getElementById('btnQuickTomorrow');
          if (tomorrowBtn) {
            tomorrowBtn.addEventListener('click', function () {
              const targetDate = this.getAttribute('data-date');
              if (dateInput && targetDate) {
                dateInput.value = targetDate;
                loadSlots();
              }
            });
          }

          const shorterBtn = document.getElementById('btnQuick1Hour');
          if (shorterBtn) {
            shorterBtn.addEventListener('click', function () {
              if (durationSelect) {
                durationSelect.value = '1';
                updatePricing();
                loadSlots();
              }
            });
          }
        })
        .catch(err => {
          console.error(err);
          slotsContainer.innerHTML = '<div class="alert alert-danger py-2 px-3 small">Failed to connect to slot server. Please refresh or retry.</div>';
        });
    }

    // Recalculate summary price
    function updatePricing() {
      const selectedOption = stationSelect ? stationSelect.options[stationSelect.selectedIndex] : null;
      const ratePerHour = selectedOption ? parseFloat(selectedOption.getAttribute('data-rate') || 100) : 100;
      const durationHours = durationSelect ? parseFloat(durationSelect.value) || 1 : 1;

      const subtotal = ratePerHour * durationHours;
      let discountAmount = 0;

      if (currentDiscountType === 'percentage') {
        discountAmount = (subtotal * currentDiscountVal) / 100;
      } else if (currentDiscountType === 'fixed') {
        discountAmount = Math.min(subtotal, currentDiscountVal);
      }

      const finalTotal = Math.max(0, subtotal - discountAmount);

      if (basePriceEl) basePriceEl.textContent = `₹${subtotal.toFixed(2)}`;
      if (discountEl) discountEl.textContent = `-₹${discountAmount.toFixed(2)}`;
      if (totalAmountEl) totalAmountEl.textContent = `₹${finalTotal.toFixed(2)}`;
      if (hiddenTotalInput) hiddenTotalInput.value = finalTotal.toFixed(2);
    }

    // Event listeners
    if (stationSelect) {
      stationSelect.addEventListener('change', () => {
        loadSlots();
        updatePricing();
      });
    }
    if (dateInput) {
      dateInput.addEventListener('change', loadSlots);
    }
    if (durationSelect) {
      durationSelect.addEventListener('change', () => {
        loadSlots();
        updatePricing();
      });
    }

    // Promo code application
    if (applyPromoBtn && promoInput) {
      applyPromoBtn.addEventListener('click', function () {
        const code = promoInput.value.trim();
        const durationHours = durationSelect ? parseFloat(durationSelect.value) || 1 : 1;

        if (!code) {
          if (promoMessageEl) {
            promoMessageEl.innerHTML = '<span class="text-danger small">Please enter a promo code.</span>';
          }
          return;
        }

        applyPromoBtn.disabled = true;
        applyPromoBtn.textContent = 'Checking...';

        fetch(`${baseUrl}api/apply-promo.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code: code, duration_hours: durationHours })
        })
          .then(res => res.json())
          .then(data => {
            applyPromoBtn.disabled = false;
            applyPromoBtn.textContent = 'Apply';

            if (data.success) {
              currentDiscountType = data.discount_type;
              currentDiscountVal = parseFloat(data.discount_value);
              if (promoMessageEl) {
                promoMessageEl.innerHTML = `<span class="text-green small"><i class="bi bi-check-circle-fill me-1"></i> ${data.message}</span>`;
              }
              updatePricing();
            } else {
              currentDiscountType = null;
              currentDiscountVal = 0;
              if (promoMessageEl) {
                promoMessageEl.innerHTML = `<span class="text-danger small"><i class="bi bi-x-circle-fill me-1"></i> ${data.message}</span>`;
              }
              updatePricing();
            }
          })
          .catch(() => {
            applyPromoBtn.disabled = false;
            applyPromoBtn.textContent = 'Apply';
            if (promoMessageEl) {
              promoMessageEl.innerHTML = '<span class="text-danger small">Error verifying code.</span>';
            }
          });
      });
    }

    // Client-side Strict Indian Phone & Email Validation
    if (custPhoneInput) {
      custPhoneInput.addEventListener('input', function () {
        let val = this.value.replace(/\D/g, '');
        if (val.length > 10 && val.startsWith('91')) {
          val = val.substring(2);
        }
        this.value = val.slice(0, 10);

        if (val.length === 10 && /^[6-9]/.test(val)) {
          this.classList.remove('is-invalid');
          this.classList.add('is-valid');
        } else {
          this.classList.remove('is-valid');
          if (val.length > 0) {
            this.classList.add('is-invalid');
          } else {
            this.classList.remove('is-invalid');
          }
        }
      });
    }

    if (custEmailInput) {
      custEmailInput.addEventListener('blur', function () {
        const val = this.value.trim();
        if (!val) {
          this.classList.remove('is-invalid', 'is-valid');
          return;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailRegex.test(val)) {
          this.classList.remove('is-invalid');
          this.classList.add('is-valid');
        } else {
          this.classList.remove('is-valid');
          this.classList.add('is-invalid');
        }
      });
    }

    // Form submission validation
    bookingForm.addEventListener('submit', function (e) {
      // Validate time slot is selected
      const selectedSlot = bookingForm.querySelector('input[name="start_time"]:checked');
      if (!selectedSlot) {
        e.preventDefault();
        alert('Please select an available start time slot before proceeding.');
        if (slotsContainer) slotsContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
      }

      if (custPhoneInput) {
        const phoneVal = custPhoneInput.value.replace(/\D/g, '');
        if (phoneVal.length !== 10 || !/^[6-9]/.test(phoneVal)) {
          e.preventDefault();
          custPhoneInput.classList.add('is-invalid');
          custPhoneInput.focus();
          alert('Please enter a valid 10-digit Indian mobile number (e.g. 9812345678) starting with 6, 7, 8, or 9.');
          return false;
        }
      }

      if (custEmailInput && custEmailInput.value.trim()) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(custEmailInput.value.trim())) {
          e.preventDefault();
          custEmailInput.classList.add('is-invalid');
          custEmailInput.focus();
          alert('Please enter a valid email address.');
          return false;
        }
      }
    });

    // Initial slot load and pricing calculation on page load
    loadSlots();
    updatePricing();
  }

  // Arena Video Tour Modal Pause on Close
  const arenaTourModal = document.getElementById('arenaTourModal');
  if (arenaTourModal) {
    arenaTourModal.addEventListener('hidden.bs.modal', function () {
      const vid = document.getElementById('arenaTourVideo');
      if (vid) vid.pause();
    });
    arenaTourModal.addEventListener('shown.bs.modal', function () {
      const vid = document.getElementById('arenaTourVideo');
      if (vid) vid.play().catch(() => {});
    });
  }
});

