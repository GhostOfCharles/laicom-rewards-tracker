(() => {
 let controller;
 document.addEventListener('click', async event => {
  const button=event.target.closest('[data-receipt-panel]'); if(!button) return;
  controller?.abort(); controller=new AbortController();
  const content=document.getElementById('receiptPanelContent');
  bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptPanelModal')).show();
  content.textContent='Loading receipt...';
  try { const response=await fetch(button.dataset.receiptPanel,{headers:{Accept:'text/html','X-Requested-With':'XMLHttpRequest'},signal:controller.signal});
   if(!response.ok || response.redirected) throw new Error('Could not load this receipt. Check your session and try again.');
   content.innerHTML=await response.text();
   updateApproval(content.querySelector('[data-reward-approval]'));
  } catch(error) { if(error.name!=='AbortError') content.textContent=error.message; }
 });
 function updateApproval(form) {
  if (!form) return;
  const remaining = new Map();
  let shortage = false, valid = true;
  const lines = [];
  form.querySelectorAll('[data-award-quantity]').forEach(input => input.remove());
  form.querySelectorAll('fieldset').forEach((fieldset, index) => {
   const choice = fieldset.querySelector('input[type="radio"]:checked');
   if (!choice || choice.disabled) { valid = false; return; }
   const {product, eligible, stock, expired} = choice.dataset;
   const available = remaining.has(product) ? remaining.get(product) : Number(stock);
   const quantity = Math.min(Number(eligible), available);
   remaining.set(product, available - quantity);
   shortage ||= quantity < Number(eligible);
   valid &&= quantity > 0 && expired !== '1';
   const label = document.createElement('div');
   label.textContent = `${fieldset.querySelector('legend').textContent}: ${eligible} eligible / ${quantity} available to award`;
   lines.push(label);
   const input = document.createElement('input');
   Object.assign(input, {type:'hidden', name:`award_quantities[${index}]`, value:quantity});
   input.dataset.awardQuantity = '';
   form.append(input);
  });
  form.querySelector('[data-allocation-summary]').replaceChildren(...lines);
  form.querySelector('[data-short-stock]').hidden = !shortage;
  const consent = form.elements.available_stock;
  if (!shortage) consent.checked = false;
  consent.disabled = !shortage;
  const button = form.querySelector('[data-approve-rewards]');
  button.disabled = !valid || (shortage && !consent.checked);
  button.textContent = shortage ? 'Approve with available stock' : 'Approve & reserve stock';
 }
 document.addEventListener('change', event => {
  updateApproval(event.target.closest('[data-reward-approval]'));
 });
 document.addEventListener('submit', event => {
  const form = event.target.closest('[data-reward-approval]');
  if (!form) return;
  if (form.querySelector('[data-approve-rewards]').disabled) { event.preventDefault(); return; }
  if (form.elements.available_stock.checked && !confirm(
   form.querySelector('[data-allocation-summary]').innerText +
   '\n\nApprove these quantities as the final award? The remaining quantity will not be owed.'
  )) event.preventDefault();
 });
 document.addEventListener('change',event=>{if(event.target.matches('[data-rejection-preset]') && event.target.value) event.target.form.querySelector('[name="reason"]').value=event.target.value;});
 document.getElementById('receiptPanelModal')?.addEventListener('hidden.bs.modal',()=>controller?.abort());
})();
