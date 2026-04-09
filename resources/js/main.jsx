import React from 'react';
import ReactDOM from 'react-dom/client';
import AppLayout from './Components/Layout/AppLayout';
import { AuthProvider } from './contexts/AuthContext';

/**
 * Main React App Mount
 * Renders AppLayout component to #root element
 */
const rootElement = document.getElementById('root');
if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(
        <React.StrictMode>
            <AuthProvider>
                <AppLayout />
            </AuthProvider>
        </React.StrictMode>
    );
}
