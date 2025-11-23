// resources/js/app.jsx

import './bootstrap'; // Pour les utilitaires JS de base (Axios, etc.)
import React from 'react';
import { createRoot } from 'react-dom/client';

/**
 * Composant Racine (Root Component)
 */
function App() {
    return (
        <div style={{ padding: '20px', border: '2px solid #61dafb', borderRadius: '8px' }}>
            <h2>Succès ! Bienvenue sur le Frontend React.</h2>
            
        </div>
    );
}

// Vérifie si l'élément <div id="app"> est présent dans le HTML
const container = document.getElementById('app');
if (container) {
    // Hydrate ou rend l'application React
    const root = createRoot(container);
    root.render(<App />);
}
