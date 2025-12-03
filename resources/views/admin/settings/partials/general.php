<?php
// Get system settings from system_settings table
$systemRepo = new \App\Repositories\SystemSettingsRepository();
$systemSettings = $systemRepo->all();
$timezone = $systemSettings['timezone']['value'] ?? config('app.timezone', 'Africa/Nairobi');
$dateFormat = $systemSettings['date_format']['value'] ?? 'Y-m-d';
$timeFormat = $systemSettings['time_format']['value'] ?? 'H:i';
$currency = $systemSettings['currency']['value'] ?? 'KES';
$currencySymbol = $systemSettings['currency_symbol']['value'] ?? 'KSh';
$taxRate = $systemSettings['tax_rate']['value'] ?? '0';
?>

<label>
    <span>Timezone</span>
    <select name="timezone" required>
        <option value="Africa/Nairobi" <?= $timezone === 'Africa/Nairobi' ? 'selected' : ''; ?>>Africa/Nairobi (EAT)</option>
        <option value="Africa/Dar_es_Salaam" <?= $timezone === 'Africa/Dar_es_Salaam' ? 'selected' : ''; ?>>Africa/Dar es Salaam (EAT)</option>
        <option value="Africa/Kampala" <?= $timezone === 'Africa/Kampala' ? 'selected' : ''; ?>>Africa/Kampala (EAT)</option>
        <option value="Africa/Kigali" <?= $timezone === 'Africa/Kigali' ? 'selected' : ''; ?>>Africa/Kigali (CAT)</option>
        <option value="Africa/Addis_Ababa" <?= $timezone === 'Africa/Addis_Ababa' ? 'selected' : ''; ?>>Africa/Addis Ababa (EAT)</option>
        <option value="Africa/Johannesburg" <?= $timezone === 'Africa/Johannesburg' ? 'selected' : ''; ?>>Africa/Johannesburg (SAST)</option>
        <option value="UTC" <?= $timezone === 'UTC' ? 'selected' : ''; ?>>UTC</option>
        <option value="America/New_York" <?= $timezone === 'America/New_York' ? 'selected' : ''; ?>>America/New York (EST)</option>
        <option value="America/Los_Angeles" <?= $timezone === 'America/Los_Angeles' ? 'selected' : ''; ?>>America/Los Angeles (PST)</option>
        <option value="Europe/London" <?= $timezone === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
        <option value="Asia/Dubai" <?= $timezone === 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai (GST)</option>
        <option value="Asia/Singapore" <?= $timezone === 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore (SGT)</option>
    </select>
    <small>This affects how dates and times are displayed throughout the system.</small>
</label>

<label>
    <span>Date Format</span>
    <select name="date_format">
        <option value="Y-m-d" <?= $dateFormat === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD (2025-01-29)</option>
        <option value="d/m/Y" <?= $dateFormat === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY (29/01/2025)</option>
        <option value="m/d/Y" <?= $dateFormat === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY (01/29/2025)</option>
        <option value="d-m-Y" <?= $dateFormat === 'd-m-Y' ? 'selected' : ''; ?>>DD-MM-YYYY (29-01-2025)</option>
        <option value="j M Y" <?= $dateFormat === 'j M Y' ? 'selected' : ''; ?>>29 Jan 2025</option>
        <option value="F j, Y" <?= $dateFormat === 'F j, Y' ? 'selected' : ''; ?>>January 29, 2025</option>
    </select>
</label>

<label>
    <span>Time Format</span>
    <select name="time_format">
        <option value="H:i" <?= $timeFormat === 'H:i' ? 'selected' : ''; ?>>24-hour (14:30)</option>
        <option value="h:i A" <?= $timeFormat === 'h:i A' ? 'selected' : ''; ?>>12-hour (02:30 PM)</option>
        <option value="h:i a" <?= $timeFormat === 'h:i a' ? 'selected' : ''; ?>>12-hour lowercase (02:30 pm)</option>
    </select>
</label>

<label>
    <span>Default Currency</span>
    <input type="text" name="currency" value="<?= htmlspecialchars($currency); ?>" maxlength="3" placeholder="KES" required>
    <small>ISO 4217 currency code (e.g., KES, USD, EUR)</small>
</label>

<label>
    <span>Currency Symbol</span>
    <input type="text" name="currency_symbol" value="<?= htmlspecialchars($currencySymbol); ?>" maxlength="10" placeholder="KSh" required>
</label>

<label>
    <span>Default Tax Rate (%)</span>
    <input type="number" name="tax_rate" value="<?= htmlspecialchars($taxRate); ?>" min="0" max="100" step="0.01" placeholder="0" required>
</label>

