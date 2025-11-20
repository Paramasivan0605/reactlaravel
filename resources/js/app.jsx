import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import LocationMenu from './pages/LocationMenu';

const el = document.getElementById('react-location-menu');
if (el) {
  const locationId = el.dataset.location;
  createRoot(el).render(<LocationMenu locationId={locationId} />);
}
