import React from 'react';

export default function LocationHeader({ location, currency, onChooseDelivery }) {
  return (
    <header className="lm-header">
      <div className="lm-header-overlay" />
      <div className="lm-header-inner container">
        <div className="lm-location-card">
          <div className="lm-location-icon">📍</div>
          <div>
            <div className="lm-now-serving">Now Serving</div>
            <h1 className="lm-location-name">{location?.location_name}</h1>
            <div className="lm-meta">
              <span className="lm-pill">{currency}</span>
              <span className="lm-pill">Open Now</span>
              <span className="lm-pill">YES MEMBER</span>
            </div>
          </div>
        </div>

        <div className="lm-actions">
          <button className="lm-delivery-btn" onClick={onChooseDelivery}>
            ORDER NOW
          </button>
        </div>
      </div>
    </header>
  );
}