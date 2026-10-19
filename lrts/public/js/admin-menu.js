(() => {
 const toggle=document.getElementById('adminMenuToggle'), panel=document.getElementById('adminSidebar'), backdrop=document.getElementById('adminSidebarBackdrop');
 if (!toggle || !panel) return;
 function set(open) { panel.classList.toggle('is-open',open); backdrop.hidden=!open; toggle.setAttribute('aria-expanded',String(open)); if(open) { panel.setAttribute('role','dialog');panel.setAttribute('aria-modal','true');panel.setAttribute('aria-label','Administrator navigation');panel.querySelector('button,a')?.focus(); } else {panel.removeAttribute('role');panel.removeAttribute('aria-modal');toggle.focus();} }
 toggle.addEventListener('click',()=>set(!panel.classList.contains('is-open')));
 panel.querySelector('[data-admin-menu-close]')?.addEventListener('click',()=>set(false));
 backdrop.addEventListener('click',()=>set(false));
 document.addEventListener('keydown',e=>{if(!panel.classList.contains('is-open')) return; if(e.key==='Escape') set(false); if(e.key==='Tab') { const links=Array.from(panel.querySelectorAll('button,a')).filter(n=>n.getClientRects().length); const first=links[0],last=links.at(-1); if(e.shiftKey && document.activeElement===first){e.preventDefault();last.focus();} else if(!e.shiftKey && document.activeElement===last){e.preventDefault();first.focus();} }});
})();
