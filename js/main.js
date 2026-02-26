// Mobile menu toggle
document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.querySelector('.nav-links');

    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const deliveryInput = document.getElementById('delivery_date');

    if (deliveryInput) {
        // Optional: tooltip with selected date/time
        deliveryInput.addEventListener('change', function () {
            const date = new Date(this.value);
            if (!isNaN(date)) {
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                this.title = "Selected: " + date.toLocaleString('en-US', options);
            }
        });

        // ────────────────────────────────────────────────
        // Strict: block any time outside 09:00–18:00
        // ────────────────────────────────────────────────
        deliveryInput.addEventListener('input', function (e) {
            const value = this.value;
            if (!value) return;

            const [datePart, timePart] = value.split('T');
            if (!timePart) return;

            const [hours, minutes] = timePart.split(':').map(Number);

            // Invalid time → reset to nearest valid boundary
            let correctedTime = null;

            if (hours < 9 || (hours === 9 && minutes < 0)) {
                correctedTime = '09:00';
            } else if (hours > 18 || (hours === 18 && minutes > 0)) {
                correctedTime = '18:00';
            }

            if (correctedTime !== null) {
                // Rebuild valid value
                this.value = datePart + 'T' + correctedTime;

                // Show clear feedback
                alert(`Delivery time is only allowed between 09:00 and 18:00.\n\nAdjusted to ${correctedTime}.`);
            }
        });

        // Extra protection: prevent form submission if time is invalid
        deliveryInput.closest('form').addEventListener('submit', function (e) {
            const value = deliveryInput.value;
            if (value) {
                const time = value.split('T')[1];
                if (time) {
                    const [h] = time.split(':').map(Number);
                    if (h < 9 || h > 18) {
                        e.preventDefault();
                        alert("Please select a delivery time between 09:00 and 18:00.");
                    }
                }
            }
        });
    }
});

document.getElementById('add-material-btn')?.addEventListener('click', function () {
    const container = document.getElementById('materials-container');
    const row = document.createElement('div');
    row.className = 'material-row';
    row.style.display = 'flex';
    row.style.gap = '1rem';
    row.style.marginBottom = '1rem';
    row.style.alignItems = 'center';

    row.innerHTML = `
        <select name="materials[]" style="flex:1; padding:0.6rem;">
            <option value="">Select Material</option>
            <?php foreach ($materials as $m): ?>
                <option value="<?= $m['mid'] ?>"><?= htmlspecialchars($m['mname']) ?> (<?= htmlspecialchars($m['munit']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="pmqty[]" min="1" placeholder="Qty" style="width:100px; padding:0.6rem;">
        <button type="button" class="btn-outline remove-material" style="color:#dc3545;">Remove</button>
    `;

    container.appendChild(row);
});

// Remove material row
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-material')) {
        e.target.closest('.material-row').remove();
    }
});

// Dynamic material rows
document.getElementById('add-material-btn')?.addEventListener('click', function () {
    const container = document.getElementById('materials-container');
    const row = document.createElement('div');
    row.className = 'material-row';
    row.style.cssText = 'display:flex; gap:1rem; margin-bottom:1rem; align-items:center;';

    row.innerHTML = `
        <select name="materials[]" style="flex:1; padding:0.6rem;">
            <option value="">Select Material</option>
            <?php foreach ($materials as $m): ?>
                <option value="<?= $m['mid'] ?>"><?= addslashes(htmlspecialchars($m['mname'])) ?> (<?= addslashes(htmlspecialchars($m['munit'])) ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="pmqty[]" min="1" placeholder="Qty" style="width:100px; padding:0.6rem;">
        <button type="button" class="btn-outline remove-material" style="color:#dc3545;">Remove</button>
    `;

    container.appendChild(row);
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-material')) {
        e.target.closest('.material-row').remove();
    }
});