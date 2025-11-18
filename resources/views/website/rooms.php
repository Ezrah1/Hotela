<?php
$website = $website ?? settings('website', []);
$roomTypes = $roomTypes ?? [];
$selectedTypeId = $selectedTypeId ?? null;
$mode = $website['room_display_mode'] ?? 'both';
$guestProfile = \App\Support\GuestPortal::user();
$defaultCheckIn = $_GET['check_in'] ?? date('Y-m-d', strtotime('+1 day'));
$defaultCheckOut = $_GET['check_out'] ?? date('Y-m-d', strtotime('+2 days'));
$slot = function () use ($rooms, $mode, $website, $roomTypes, $selectedTypeId, $guestProfile, $defaultCheckIn, $defaultCheckOut) {
    ob_start(); ?>
    <section class="page-hero page-hero--boxed">
        <div class="container">
            <div class="page-hero__box">
                <header>
                    <p class="eyebrow"><?= htmlspecialchars($website['rooms_intro'] ?? 'Every suite is staged, serviced, and updated from Hotela.'); ?></p>
                    <h1>Book a specific room in two steps</h1>
                    <p>Pick dates below and we’ll show real-time availability, pricing, and tailored room suggestions.</p>
                </header>
                <form id="roomFilters" class="room-filter-panel">
                    <div class="room-filter-grid">
                        <label>
                            <span>Check-in</span>
                            <input type="date" name="check_in" value="<?= htmlspecialchars($defaultCheckIn); ?>" min="<?= date('Y-m-d'); ?>">
                        </label>
                        <label>
                            <span>Check-out</span>
                            <input type="date" name="check_out" value="<?= htmlspecialchars($defaultCheckOut); ?>" min="<?= date('Y-m-d', strtotime('+1 day')); ?>">
                        </label>
                        <label>
                            <span>Room type</span>
                            <select name="room_type_id">
                                <option value="">Any</option>
                                <?php foreach ($roomTypes as $type): ?>
                                    <option value="<?= (int)$type['id']; ?>" <?= $selectedTypeId === (int)$type['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>Guests</span>
                            <input type="number" name="guests" min="1" value="2">
                        </label>
                        <label>
                            <span>Search</span>
                            <input type="text" name="query" placeholder="Room name, number, keywords">
                        </label>
                    </div>
                    <div class="room-filter-actions">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="available_only" checked>
                            <span>Available rooms only</span>
                        </label>
                        <button type="reset" class="btn btn-ghost btn-small">Clear filters</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <section class="container rooms-grid" id="room-list">
        <?php foreach ($rooms as $room): ?>
            <?php
            $status = strtolower($room['status'] ?? 'available');
            $maxGuests = (int)($room['max_guests'] ?? $room['capacity'] ?? 2);
            $roomLabel = trim($room['display_name'] ?? $room['room_number'] ?? $room['room_type_name'] ?? '');
            $roomTypeName = trim($room['room_type_name'] ?? 'Room');
            $isSameLabelAndType = strcasecmp($roomLabel, $roomTypeName) === 0;
            // Use room image if available, otherwise fall back to room type image
            $photo = $room['image'] ?? $room['room_type_image'] ?? $room['photo_url'] ?? $room['cover_image'] ?? ($room['room_type_cover'] ?? null);
            if (!$photo) {
                $photo = 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=80';
            }
            $roomDescription = trim((string)($room['description'] ?? ''));
            if ($roomDescription === '') {
                $roomDescription = trim((string)($room['room_type_description'] ?? ''));
            }
            $amenitiesRaw = $room['room_type_amenities'] ?? $room['amenities'] ?? [];
            if (is_string($amenitiesRaw)) {
                $decoded = json_decode($amenitiesRaw, true);
                $amenitiesRaw = is_array($decoded) ? $decoded : array_map('trim', explode(',', $amenitiesRaw));
            }
            if (!is_array($amenitiesRaw)) {
                $amenitiesRaw = [];
            }
            $amenitiesList = array_slice(array_filter(array_map(function ($item) {
                if (is_string($item)) {
                    return trim($item);
                }
                if (is_array($item) && isset($item['label'])) {
                    return trim($item['label']);
                }
                return null;
            }, $amenitiesRaw)), 0, 4);

            $searchHaystack = strtolower(trim(
                ($room['display_name'] ?? '') . ' ' .
                ($room['room_number'] ?? '') . ' ' .
                ($room['room_type_name'] ?? '') . ' ' .
                ($room['description'] ?? '') . ' ' .
                ($room['room_type_description'] ?? '')
            ));
            ?>
            <article class="room-card"
                data-room-id="<?= (int)$room['id']; ?>"
                data-room-type-id="<?= (int)$room['room_type_id']; ?>"
                data-room-type-name="<?= htmlspecialchars($roomTypeName); ?>"
                data-room-name="<?= htmlspecialchars($roomLabel); ?>"
                data-room-number="<?= htmlspecialchars($room['room_number'] ?? ''); ?>"
                data-status="<?= htmlspecialchars($status); ?>"
                data-max-guests="<?= $maxGuests; ?>"
                data-search="<?= htmlspecialchars($searchHaystack); ?>"
                data-base-rate="<?= htmlspecialchars($room['base_rate'] ?? 0); ?>"
                data-photo="<?= htmlspecialchars($photo); ?>"
                data-description="<?= htmlspecialchars($roomDescription); ?>">
                <div class="room-card__media" style="background-image: url('<?= htmlspecialchars($photo); ?>');"></div>
                <div class="room-card__body">
                    <div class="room-card__head">
                        <p class="room-card__eyebrow">
                            <?php
                            $eyebrowParts = [ucfirst($status)];
                            if (!$isSameLabelAndType && $roomTypeName) {
                                $eyebrowParts[] = $roomTypeName;
                            }
                            echo htmlspecialchars(implode(' · ', array_filter($eyebrowParts)));
                            ?>
                        </p>
                        <h3>
                            <?php
                            $nameSegments = [];
                            if (($mode === 'name' || $mode === 'both') && $roomLabel !== '') {
                                $nameSegments[] = $roomLabel;
                            }
                            if (($mode === 'type' || $mode === 'both') && $roomTypeName !== '' && ($mode === 'type' || !$isSameLabelAndType)) {
                                if (empty($nameSegments) || strcasecmp(end($nameSegments), $roomTypeName) !== 0) {
                                    $nameSegments[] = $roomTypeName;
                                }
                            }
                            if (empty($nameSegments)) {
                                $nameSegments[] = $roomTypeName ?: $roomLabel ?: 'Room';
                            }
                            echo htmlspecialchars(implode(' · ', $nameSegments));
                            ?>
                        </h3>
                        <?php if (!empty($room['base_rate'])): ?>
                            <p class="room-card__rate">
                                <span>From</span>
                                <strong>KES <?= number_format($room['base_rate'], 2); ?></strong>
                                <span>/ night</span>
                            </p>
                        <?php endif; ?>
                    </div>
                    <p class="room-card__copy"><?= htmlspecialchars($roomDescription ?: 'Spacious interiors, complimentary Wi-Fi, workspace, and premium linens.'); ?></p>
                    <?php if ($amenitiesList): ?>
                        <ul class="room-amenities">
                            <?php foreach ($amenitiesList as $amenity): ?>
                                <li><?= htmlspecialchars($amenity); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="room-card__meta">
                        <span>Max <?= $maxGuests; ?> guests</span>
                        <span><?= htmlspecialchars($room['bed_type'] ?? 'Flexible bedding'); ?></span>
                    </div>
                    <div class="room-card__actions">
                        <button class="btn btn-primary btn-small" type="button" data-book-room>Book This Room</button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($rooms)): ?>
            <p class="empty-state">Room types will appear here once configured in the admin dashboard.</p>
        <?php endif; ?>
    </section>

    <div id="roomBookingPanel" class="booking-panel" data-guest='<?= htmlspecialchars(json_encode([
        'guest_name' => $guestProfile['guest_name'] ?? '',
        'guest_email' => $guestProfile['guest_email'] ?? '',
        'guest_phone' => $guestProfile['guest_phone'] ?? '',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>'>
        <div class="booking-panel__backdrop" data-close-panel></div>
        <div class="booking-panel__card">
            <button class="booking-panel__close" type="button" data-close-panel aria-label="Close booking panel">&times;</button>
            <div class="booking-panel__step is-active" data-step="dates">
                <h2>Step 1 · Dates & pricing</h2>
                <p>Select your stay to check live availability and get an instant quote.</p>
                <form id="bookingDatesForm">
                    <input type="hidden" name="room_type_id">
                    <input type="hidden" name="room_id">
                    <div class="form-grid">
                        <label>
                            <span>Check-in</span>
                            <input type="date" name="check_in" min="<?= date('Y-m-d'); ?>" required>
                        </label>
                        <label>
                            <span>Check-out</span>
                            <input type="date" name="check_out" min="<?= date('Y-m-d', strtotime('+1 day')); ?>" required>
                        </label>
                        <label>
                            <span>Guests</span>
                            <input type="number" name="adults" min="1" value="2" required>
                        </label>
                        <label>
                            <span>Children</span>
                            <input type="number" name="children" min="0" value="0">
                        </label>
                    </div>
                    <button class="btn btn-primary btn-full" type="submit">Check availability</button>
                    <div class="booking-panel__status" data-availability-status></div>
                    <div class="booking-panel__suggestions" data-suggestions></div>
                </form>
            </div>
            <div class="booking-panel__step" data-step="guest">
                <h2>Step 2 · Guest details</h2>
                <p>We pre-filled your profile when available. Review and confirm to reserve instantly.</p>
                <div class="booking-summary">
                    <div>
                        <p class="eyebrow">Room</p>
                        <h3 data-summary-room>Room</h3>
                        <p data-summary-dates>Dates</p>
                    </div>
                    <dl>
                        <div>
                            <dt>Nights</dt>
                            <dd data-summary-nights>-</dd>
                        </div>
                        <div>
                            <dt>Nightly rate</dt>
                            <dd data-summary-nightly>-</dd>
                        </div>
                        <div>
                            <dt>Total</dt>
                            <dd data-summary-total>-</dd>
                        </div>
                    </dl>
                </div>
                <form id="bookingGuestForm" method="post" action="<?= base_url('booking'); ?>">
                    <input type="hidden" name="check_in">
                    <input type="hidden" name="check_out">
                    <input type="hidden" name="adults">
                    <input type="hidden" name="children">
                    <input type="hidden" name="room_type_id">
                    <input type="hidden" name="room_id">
                    <input type="hidden" name="total_amount">
                    <div class="form-grid">
                        <label>
                            <span>Full name</span>
                            <input type="text" name="guest_name" placeholder="Jane Mwangi" required>
                        </label>
                        <label>
                            <span>Email</span>
                            <input type="email" name="guest_email" placeholder="you@example.com">
                        </label>
                        <label>
                            <span>Phone</span>
                            <input type="tel" name="guest_phone" placeholder="+254700000000">
                        </label>
                    </div>
                    <label>
                        <span>Special requests (optional)</span>
                        <textarea name="special_requests" rows="3" placeholder="Airport pickup, dietary notes, celebration setup..."></textarea>
                    </label>
                    <button class="btn btn-primary btn-full" type="submit">Confirm booking</button>
                    <p class="booking-panel__fine-print">We’ll email/SMS your confirmation instantly. Pay on arrival unless online payments are enabled.</p>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filters = document.getElementById('roomFilters');
            const cards = Array.from(document.querySelectorAll('.room-card'));
            const emptyState = document.querySelector('.rooms-grid .empty-state');
            const panel = document.getElementById('roomBookingPanel');
            const checkForm = document.getElementById('bookingDatesForm');
            const guestForm = document.getElementById('bookingGuestForm');
            const suggestionsBox = panel ? panel.querySelector('[data-suggestions]') : null;
            const statusBox = panel ? panel.querySelector('[data-availability-status]') : document.createElement('div');
            const summary = {
                room: panel ? panel.querySelector('[data-summary-room]') : null,
                dates: panel ? panel.querySelector('[data-summary-dates]') : null,
                nights: panel ? panel.querySelector('[data-summary-nights]') : null,
                nightly: panel ? panel.querySelector('[data-summary-nightly]') : null,
                total: panel ? panel.querySelector('[data-summary-total]') : null,
            };
            const steps = panel ? panel.querySelectorAll('.booking-panel__step') : [];
            const guestDataset = panel ? JSON.parse(panel.dataset.guest || '{}') : {};
            const config = {
                checkUrl: "<?= base_url('booking/check'); ?>",
            };
            const state = {
                room: null,
                availability: null,
            };

            const getFilterValue = (selector) => {
                if (!filters) return '';
                const field = filters.querySelector(selector);
                return field ? (field.value || '') : '';
            };
            const getFilterChecked = (selector) => {
                if (!filters) return false;
                const field = filters.querySelector(selector);
                return field ? !!field.checked : false;
            };

            const filterInputs = filters ? filters.querySelectorAll('input, select') : [];
            filterInputs.forEach(input => {
                input.addEventListener('input', applyFilters);
            });
            if (filters) {
                filters.addEventListener('reset', () => {
                    setTimeout(applyFilters, 50);
                });
            }

            function applyFilters() {
                const typeValue = getFilterValue('[name="room_type_id"]');
                const queryValue = getFilterValue('[name="query"]').toLowerCase();
                const guestValue = parseInt(getFilterValue('[name="guests"]') || '0', 10);
                const availableOnly = getFilterChecked('[name="available_only"]');

                let visibleCount = 0;
                cards.forEach(card => {
                    let visible = true;

                    if (typeValue && card.dataset.roomTypeId !== typeValue) {
                        visible = false;
                    }
                    if (availableOnly && card.dataset.status !== 'available') {
                        visible = false;
                    }
                    if (guestValue && parseInt(card.dataset.maxGuests, 10) < guestValue) {
                        visible = false;
                    }
                    if (queryValue && !card.dataset.search.includes(queryValue)) {
                        visible = false;
                    }

                    card.style.display = visible ? '' : 'none';
                    if (visible) {
                        visibleCount += 1;
                    }
                });

                if (emptyState) {
                    emptyState.style.display = visibleCount ? 'none' : 'block';
                }
            }

            applyFilters();

            document.querySelectorAll('[data-book-room]').forEach(button => {
                button.addEventListener('click', event => {
                    const card = event.currentTarget.closest('.room-card');
                    if (!card || !panel) return;

                    state.room = {
                        id: card.dataset.roomId,
                        typeId: card.dataset.roomTypeId,
                        typeName: card.dataset.roomTypeName,
                        name: card.dataset.roomName,
                        number: card.dataset.roomNumber,
                        photo: card.dataset.photo,
                        baseRate: parseFloat(card.dataset.baseRate || '0'),
                    };

                    statusBox.textContent = '';
                    suggestionsBox.innerHTML = '';
                    state.availability = null;
                    switchStep('dates');

                    checkForm.querySelector('[name="room_type_id"]').value = state.room.typeId;
                    checkForm.querySelector('[name="room_id"]').value = state.room.id;

                    const masterCheckIn = getFilterValue('[name="check_in"]');
                    const masterCheckOut = getFilterValue('[name="check_out"]');
                    checkForm.querySelector('[name="check_in"]').value = masterCheckIn || "<?= htmlspecialchars($defaultCheckIn); ?>";
                    checkForm.querySelector('[name="check_out"]').value = masterCheckOut || "<?= htmlspecialchars($defaultCheckOut); ?>";

                    guestForm.reset();
                    guestForm.querySelector('[name="guest_name"]').value = guestDataset.guest_name || '';
                    guestForm.querySelector('[name="guest_email"]').value = guestDataset.guest_email || '';
                    guestForm.querySelector('[name="guest_phone"]').value = guestDataset.guest_phone || '';

                    populateSummary('-', '-', '-');

                    panel.classList.add('is-visible');
                    document.body.classList.add('no-scroll');
                });
            });

            if (panel) {
                panel.querySelectorAll('[data-close-panel]').forEach(el => {
                    el.addEventListener('click', closePanel);
                });
            }

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    closePanel();
                }
            });

            function closePanel() {
                if (!panel) return;
                panel.classList.remove('is-visible');
                document.body.classList.remove('no-scroll');
            }

            function switchStep(step) {
                steps.forEach(node => {
                    node.classList.toggle('is-active', node.dataset.step === step);
                });
            }

            function populateSummary(dates, nights, total) {
                if (summary.room) {
                    summary.room.textContent = state.room ? `${state.room.name} · ${state.room.typeName}` : 'Room';
                }
                if (summary.dates) {
                    summary.dates.textContent = dates;
                }
                if (summary.nights) {
                    summary.nights.textContent = nights;
                }
                if (summary.nightly) {
                    summary.nightly.textContent = '-';
                }
                if (summary.total) {
                    summary.total.textContent = total;
                }
            }

            if (checkForm) {
                checkForm.addEventListener('submit', event => {
                    event.preventDefault();
                    if (!state.room) {
                        statusBox.textContent = 'Select a room to continue.';
                        return;
                    }

                    const formData = new FormData(checkForm);
                    const payload = Object.fromEntries(formData.entries());

                    statusBox.textContent = 'Checking availability...';
                    statusBox.classList.remove('error');

                    fetch(config.checkUrl, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: new URLSearchParams(payload),
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.error) {
                                throw new Error(data.error);
                            }

                            state.availability = data;
                            if (!data.available) {
                                statusBox.textContent = 'This room is unavailable for those dates. Try a suggestion below.';
                                statusBox.classList.add('error');
                                renderSuggestions(data.suggestions);
                                return;
                            }

                            statusBox.textContent = 'Great news! This room is free. Proceed to guest details.';
                            statusBox.classList.remove('error');
                            if (suggestionsBox) {
                                suggestionsBox.innerHTML = '';
                            }

                            const checkIn = formData.get('check_in');
                            const checkOut = formData.get('check_out');
                            const adults = formData.get('adults');
                            const children = formData.get('children');

                            guestForm.querySelector('[name="check_in"]').value = checkIn;
                            guestForm.querySelector('[name="check_out"]').value = checkOut;
                            guestForm.querySelector('[name="adults"]').value = adults;
                            guestForm.querySelector('[name="children"]').value = children;
                            guestForm.querySelector('[name="room_type_id"]').value = state.room.typeId;
                            guestForm.querySelector('[name="room_id"]').value = data.room && data.room.id ? data.room.id : '';
                            guestForm.querySelector('[name="total_amount"]').value = data.pricing.total;

                            if (summary.dates) {
                                summary.dates.textContent = `${formatDate(checkIn)} → ${formatDate(checkOut)}`;
                            }
                            if (summary.nights) {
                                summary.nights.textContent = `${data.pricing.nights} night${data.pricing.nights > 1 ? 's' : ''}`;
                            }
                            if (summary.nightly) {
                                summary.nightly.textContent = `KES ${formatCurrency(data.pricing.nightly_rate)}`;
                            }
                            if (summary.total) {
                                summary.total.textContent = `KES ${formatCurrency(data.pricing.total)}`;
                            }

                            switchStep('guest');
                        })
                        .catch(error => {
                            statusBox.textContent = error.message || 'Unable to check availability.';
                            statusBox.classList.add('error');
                        });
                });
            }

            function renderSuggestions(items = []) {
                if (!suggestionsBox) return;
                if (!items.length) {
                    suggestionsBox.innerHTML = '<p>No alternate rooms available for the selected dates. Try adjusting your stay.</p>';
                    return;
                }

                suggestionsBox.innerHTML = `
                    <p>Other rooms available for these dates:</p>
                    <ul>
                        ${items.map(item => `
                            <li>
                                <strong>${item.room_type_name}</strong>
                                <span>${item.room_name}</span>
                                <span>KES ${formatCurrency(item.base_rate)} / night</span>
                            </li>
                        `).join('')}
                    </ul>
                `;
            }

            if (guestForm) {
                guestForm.addEventListener('submit', () => {
                    const button = guestForm.querySelector('button[type="submit"]');
                    if (button) {
                        button.disabled = true;
                        button.textContent = 'Submitting...';
                    }
                });
            }

            function formatDate(value) {
                const date = new Date(value);
                return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
            }

            function formatCurrency(amount) {
                return Number(amount || 0).toLocaleString();
            }
        });
    </script>
    <?php
    return ob_get_clean();
};
$pageTitle = 'Rooms | ' . (settings('branding.name', 'Hotela'));
include view_path('layouts/public.php');

