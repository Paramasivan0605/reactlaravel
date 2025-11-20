import React, { useEffect } from 'react';

export default function DeliveryModal({ open, onClose, locationId }) {
  useEffect(() => {
    function onKey(e) {
      if (e.key === 'Escape') onClose();
    }
    if (open) document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div className="lm-modal-backdrop" onMouseDown={onClose}>
      <div className="lm-modal" onMouseDown={e => e.stopPropagation()}>
        <div className="lm-modal-header">
          <div className="lm-modal-icon">🍽️</div>
          <h3>Choose Your Order Type</h3>
          <p className="lm-sub">Select how you'd like to receive your order</p>
        </div>

        <div className="lm-modal-grid">
          <button className="lm-option" onClick={() => { sessionStorage.setItem('delivery_type','Doorstep Delivery'); sessionStorage.setItem('location_id', locationId); onClose(); }}>
            <div className="lm-option-emoji">🚗</div>
            <h4>Doorstep Delivery</h4>
            <p>We'll bring it to you</p>
            <div className="lm-badge">Most Popular</div>
          </button>

          <button className="lm-option" onClick={() => { sessionStorage.setItem('delivery_type','Counter Pickup'); sessionStorage.setItem('location_id', locationId); onClose(); }}>
            <div className="lm-option-emoji">🏪</div>
            <h4>Counter Pickup</h4>
            <p>Pick up from restaurant</p>
            <div className="lm-badge">Fast & Easy</div>
          </button>
        </div>
      </div>
    </div>
  );
}