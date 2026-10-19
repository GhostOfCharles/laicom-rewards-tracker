document.addEventListener('DOMContentLoaded', () => {
            const dropdown = document.getElementById('customerNotificationsDropdown');
            dropdown?.addEventListener('show.bs.dropdown', async () => {
                try {
                    const preview = await fetch(window.rtsCustomerConfig.notificationsPreview, {headers:{'Accept':'text/html','X-Requested-With':'XMLHttpRequest'}});
                    if(!preview.ok || preview.redirected) return;
                    dropdown.querySelector('.lrts-notification-list').innerHTML=await preview.text();
                    const ids=Array.from(dropdown.querySelectorAll('[data-notification-id]')).map(node=>Number(node.dataset.notificationId));
                    const response = await fetch(window.rtsCustomerConfig.notificationsRead, {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': window.rtsCustomerConfig.csrf, 'Accept': 'application/json','Content-Type':'application/json'}, body:JSON.stringify({ids})
                    });
                    if (response.ok) { const data=await response.json(),badge=dropdown.querySelector('.badge'); if(badge) { badge.hidden=data.unread_count<1; badge.textContent=data.unread_count; } }
                } catch (_) {}
            });

            document.querySelectorAll('[data-open-reward-ticket]').forEach(button => button.addEventListener('click', () => {
                const receiptSelect = document.getElementById('lrtsCreateReceipt');
                const receiptId = button.dataset.receiptId;
                const option = receiptSelect?.querySelector(`option[value="${receiptId}"]`);
                if (!option) return;
                receiptSelect.value = receiptId;
                document.getElementById('lrtsCreateSubject').value = `Reward question for order ${option.dataset.number}`;
                document.getElementById('lrtsCreateCategory').value = 'rewards';
                document.querySelectorAll('#lrtsCreateChips .lrts-create-chip').forEach(chip => chip.classList.toggle('is-selected', chip.dataset.value === 'rewards'));
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('createTicketModal'));
                modal.show();
            }));

            const slipInput = document.getElementById('receiptSlipInput');
            slipInput?.addEventListener('change', () => {
                const file = slipInput.files?.[0];
                document.getElementById('receiptSlipPreview').textContent = file ? `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB` : 'No image selected.';
            });

            document.getElementById('orderForm')?.addEventListener('submit', event => {
                if (!document.querySelector('#hidden_inputs_container input[name$="[product_name]"]')) {
                    event.preventDefault();
                    alert('Add at least one purchased product before submitting.');
                }
            });
        });