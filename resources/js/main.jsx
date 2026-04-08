import React from 'react';
import ReactDOM from 'react-dom/client';
import AppLayout from './Components/Layout/AppLayout';

/**
 * Main React App Mount
 * Renders AppLayout component to #root element
 */
const rootElement = document.getElementById('root');
if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(
        <React.StrictMode>
            <AppLayout />
        </React.StrictMode>
    );
}
