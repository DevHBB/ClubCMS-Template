/**
 * ClubCMS — JavaScript principal
 * Vanilla JS, zéro dépendance
 */

'use strict';

// ── Notifications toast ────────────────────────────────────────
const Toast = {
  container: null,
  init() {
    this.container = document.createElement('div');
    this.container.id = 'toast-container';
    this.container.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;pointer-events:none';
    document.body.appendChild(this.container);
  },
  show(message, type = 'info', duration = 4000) {
    if (!this.container) this.init();
    const colors = { success: '#059669', error: '#dc2626', info: '#1d4ed8', warning: '#d97706' };
    const icons  = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    const toast  = document.createElement('div');
    toast.style.cssText = `
      background:#fff;border:1px solid #e2e8f0;border-left:3px solid ${colors[type]};
      border-radius:8px;padding:.75rem 1rem;min-width:260px;max-width:380px;
      box-shadow:0 8px 24px rgba(0,0,0,.12);display:flex;align-items:center;gap:.5rem;
      font-size:.875rem;pointer-events:all;
      animation:slideIn .25s ease;
    `;
    toast.innerHTML = `${icons[type]}<span>${message}</span>`;
    this.container.appendChild(toast);
    setTimeout(() => {
      toast.style.animation = 'slideOut .25s ease forwards';
      toast.addEventListener('animationend', () => toast.remove());
    }, duration);
  }
};

// ── Requêtes AJAX ─────────────────────────────────────────────
async function apiRequest(url, method = 'GET', data = null) {
  const opts = {
    method,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  };
  if (data) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(data);
  }
  const res = await fetch(url, opts);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

// ── Confirmation ──────────────────────────────────────────────
function confirm_action(message, callback) {
  const modal = document.createElement('div');
  modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;display:flex;align-items:center;justify-content:center;padding:1rem';
  modal.innerHTML = `
    <div style="background:#fff;border-radius:12px;padding:2rem;max-width:400px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3)">
      <p style="font-size:1rem;margin-bottom:1.5rem;color:#1e293b">${message}</p>
      <div style="display:flex;gap:1rem;justify-content:center">
        <button id="modal-cancel" style="padding:.6rem 1.5rem;border:1.5px solid #e2e8f0;border-radius:6px;background:#fff;cursor:pointer;font-size:.9rem">Annuler</button>
        <button id="modal-confirm" style="padding:.6rem 1.5rem;border:none;border-radius:6px;background:#dc2626;color:#fff;cursor:pointer;font-size:.9rem;font-weight:600">Confirmer</button>
      </div>
    </div>`;
  document.body.appendChild(modal);
  document.getElementById('modal-cancel').onclick  = () => modal.remove();
  document.getElementById('modal-confirm').onclick = () => { modal.remove(); callback(); };
  modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
}

// ── Galerie lightbox ──────────────────────────────────────────
function initLightbox() {
  const imgs = document.querySelectorAll('[data-lightbox]');
  if (!imgs.length) return;

  let currentIndex = 0;
  const allSrcs = [...imgs].map(i => ({ src: i.dataset.lightbox, caption: i.dataset.caption || '' }));

  function open(index) {
    currentIndex = index;
    const item = allSrcs[index];
    const lb = document.createElement('div');
    lb.id = 'lightbox';
    lb.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem';
    lb.innerHTML = `
      <button onclick="document.getElementById('lightbox').remove()" style="position:absolute;top:1rem;right:1rem;background:rgba(255,255,255,.15);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:1.2rem;cursor:pointer">✕</button>
      ${index > 0 ? `<button onclick="lbNav(-1)" style="position:absolute;left:1rem;background:rgba(255,255,255,.15);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:1.4rem;cursor:pointer">‹</button>` : ''}
      ${index < allSrcs.length-1 ? `<button onclick="lbNav(1)" style="position:absolute;right:1rem;background:rgba(255,255,255,.15);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:1.4rem;cursor:pointer">›</button>` : ''}
      <div style="text-align:center">
        <img src="${item.src}" style="max-width:90vw;max-height:82vh;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,.5)">
        ${item.caption ? `<p style="color:rgba(255,255,255,.7);margin-top:.75rem;font-size:.9rem">${item.caption}</p>` : ''}
      </div>`;
    document.body.appendChild(lb);
    lb.onclick = (e) => { if (e.target === lb) lb.remove(); };
  }

  window.lbNav = (dir) => {
    document.getElementById('lightbox')?.remove();
    open(currentIndex + dir);
  };

  imgs.forEach((img, i) => {
    img.style.cursor = 'pointer';
    img.addEventListener('click', () => open(i));
  });
}

// ── Onglets ───────────────────────────────────────────────────
function initTabs() {
  document.querySelectorAll('[data-tabs]').forEach(container => {
    const tabs    = container.querySelectorAll('[data-tab]');
    const panels  = container.querySelectorAll('[data-panel]');
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        tabs.forEach(t  => t.classList.toggle('active', t.dataset.tab === target));
        panels.forEach(p => p.classList.toggle('active', p.dataset.panel === target));
      });
    });
  });
}

// ── Animations au scroll ──────────────────────────────────────
function initScrollReveal() {
  if (!('IntersectionObserver' in window)) return;
  const items = document.querySelectorAll('.reveal');
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('revealed'); obs.unobserve(e.target); } });
  }, { threshold: 0.1 });
  items.forEach(i => obs.observe(i));
}

// ── Init global ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  Toast.init();
  initLightbox();
  initTabs();
  initScrollReveal();

  // Flash messages en session (injectés en data attribute)
  const flashEl = document.getElementById('flash-message');
  if (flashEl) {
    Toast.show(flashEl.dataset.message, flashEl.dataset.type || 'info');
  }
});

// ── CSS animations ────────────────────────────────────────────
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn { from { transform:translateX(100%);opacity:0; } to { transform:none;opacity:1; } }
  @keyframes slideOut { to { transform:translateX(110%);opacity:0; } }
  .reveal { opacity:0; transform:translateY(24px); transition:opacity .5s ease, transform .5s ease; }
  .reveal.revealed { opacity:1; transform:none; }
`;
document.head.appendChild(style);

// Expose globalement
window.Toast = Toast;
window.apiRequest = apiRequest;
window.confirm_action = confirm_action;
