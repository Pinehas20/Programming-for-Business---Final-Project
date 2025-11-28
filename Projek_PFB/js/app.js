document.addEventListener('DOMContentLoaded', function() {
    initDurationCalculator();
    initFormValidation();
    initConfirmModals();
    initNotifications();
});

function initDurationCalculator() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const durationDisplay = document.getElementById('duration_display');

    if (startTimeInput && endTimeInput) {
        function calculateDuration() {
            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;

            if (startTime && endTime) {
                const start = new Date(`2000-01-01T${startTime}`);
                const end = new Date(`2000-01-01T${endTime}`);
                
                if (end > start) {
                    const diffMs = end - start;
                    const diffMins = Math.floor(diffMs / 60000);
                    const hours = Math.floor(diffMins / 60);
                    const mins = diffMins % 60;
                    
                    if (durationDisplay) {
                        durationDisplay.textContent = `${hours} jam ${mins} menit`;
                        durationDisplay.classList.remove('text-danger');
                        durationDisplay.classList.add('text-primary');
                    }
                } else {
                    if (durationDisplay) {
                        durationDisplay.textContent = 'Jam selesai harus lebih besar dari jam mulai';
                        durationDisplay.classList.remove('text-primary');
                        durationDisplay.classList.add('text-danger');
                    }
                }
            }
        }

        startTimeInput.addEventListener('change', calculateDuration);
        endTimeInput.addEventListener('change', calculateDuration);
    }
}

function initFormValidation() {
    const overtimeForm = document.getElementById('overtime_form');
    
    if (overtimeForm) {
        overtimeForm.addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            const reason = document.getElementById('reason').value;

            if (!startTime || !endTime || !reason.trim()) {
                e.preventDefault();
                showNotification('Semua field harus diisi!', 'danger');
                return false;
            }

            const start = new Date(`2000-01-01T${startTime}`);
            const end = new Date(`2000-01-01T${endTime}`);

            if (end <= start) {
                e.preventDefault();
                showNotification('Jam selesai harus lebih besar dari jam mulai!', 'danger');
                return false;
            }

            return true;
        });
    }
}

function initConfirmModals() {
    const approveButtons = document.querySelectorAll('.btn-approve-modal');
    const rejectButtons = document.querySelectorAll('.btn-reject-modal');
    
    approveButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            document.getElementById('confirmActionId').value = id;
            document.getElementById('confirmActionType').value = 'approve';
            document.getElementById('confirmModalLabel').textContent = 'Konfirmasi Persetujuan';
            document.getElementById('confirmModalBody').innerHTML = `
                <p>Apakah Anda yakin ingin <strong class="text-success">menyetujui</strong> pengajuan lembur dari <strong>${name}</strong>?</p>
            `;
            document.getElementById('confirmActionBtn').className = 'btn btn-success';
            document.getElementById('confirmActionBtn').textContent = 'Ya, Setujui';
        });
    });

    rejectButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            document.getElementById('confirmActionId').value = id;
            document.getElementById('confirmActionType').value = 'reject';
            document.getElementById('confirmModalLabel').textContent = 'Konfirmasi Penolakan';
            document.getElementById('confirmModalBody').innerHTML = `
                <p>Apakah Anda yakin ingin <strong class="text-danger">menolak</strong> pengajuan lembur dari <strong>${name}</strong>?</p>
            `;
            document.getElementById('confirmActionBtn').className = 'btn btn-danger';
            document.getElementById('confirmActionBtn').textContent = 'Ya, Tolak';
        });
    });
}

function initNotifications() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

function showNotification(message, type = 'success') {
    const container = document.getElementById('notification-container') || createNotificationContainer();
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show notification-toast`;
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 150);
    }, 5000);
}

function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notification-container';
    container.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 1050;';
    document.body.appendChild(container);
    return container;
}

function formatDuration(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}j ${mins}m`;
}
