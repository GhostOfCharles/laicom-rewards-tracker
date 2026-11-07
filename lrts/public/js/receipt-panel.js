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
  } catch(error) { if(error.name!=='AbortError') content.textContent=error.message; }
 });
 document.addEventListener('change',event=>{if(event.target.matches('[data-rejection-preset]') && event.target.value) event.target.form.querySelector('[name="reason"]').value=event.target.value;});
 document.getElementById('receiptPanelModal')?.addEventListener('hidden.bs.modal',()=>controller?.abort());
})();
