import '../css/app.css';
import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import Auth from './components/Auth';
import App from './App.jsx';

// Primary mount point for the React application
const rootEl = document.getElementById('root');
if (rootEl) {
	const root = createRoot(rootEl);
	root.render(React.createElement(App));
}

// Backwards-compatible small mount for the auth test page
const authEl = document.getElementById('auth-root');
if (authEl) {
	const root = createRoot(authEl);
	root.render(React.createElement(Auth));
}
