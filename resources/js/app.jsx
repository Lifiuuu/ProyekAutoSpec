import './bootstrap';
import './apiClient';
import './nlp-to-sql';
import React from 'react';
import { createRoot } from 'react-dom/client';
import AppLayout from './Components/Layout/AppLayout';

/**
 * Entry point for the React app.
 * Mounts React application to element with id="app" (matches Blade view).
 */
const rootElement = document.getElementById('app');
if (rootElement) {
  const root = createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <AppLayout />
    </React.StrictMode>
  );
}
