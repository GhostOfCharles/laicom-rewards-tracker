document.addEventListener('click', event => {
 const button=event.target.closest('[data-resubmit]'); if(!button) return;
 if(document.querySelector('#item_listbox .lrts-order-item') && !confirm('Replace the current purchase list with this order?')) return;
 while(!document.getElementById('remove_item_btn').disabled) document.getElementById('remove_item_btn').click();
 document.querySelectorAll('#item_listbox .lrts-order-item').forEach(row=>{row.click();document.getElementById('remove_item_btn').click();});
 bootstrap.Tab.getOrCreateInstance(document.getElementById('order-tab')).show();
 JSON.parse(button.dataset.resubmit).forEach(item=>{
  const option=Array.from(document.querySelectorAll('.lrts-product-combobox-option')).find(o=>o.dataset.productName===item.name);
  if(!option) return; option.click(); document.getElementById('product_quantity').value=item.quantity; document.getElementById('add_item_btn').click();
 });
 document.querySelector('[name="salesman_order_number"]').value='';
 document.querySelector('[name="order_date"]').focus();
});
