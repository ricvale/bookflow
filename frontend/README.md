# Frontend

## Purpose
Modern, vanilla JavaScript frontend for the BookFlow booking system.

## Stack
- **Vanilla JavaScript** (ES6+, no framework)
- **CSS3** with CSS Variables for theming
- **Vite** for development and bundling (optional, can be added later)
- **Web Components** for reusable UI elements

## Architecture
```
frontend/
├── src/
│   ├── components/     # Reusable Web Components
│   ├── pages/          # Page-level components
│   ├── services/       # API client, state management
│   └── utils/          # Helper functions
├── public/             # Static assets
└── assets/             # Images, fonts, etc.
```

## API Communication
- **REST API** to backend (JSON)
- **JWT** for authentication
- **Tenant ID** extracted from JWT (never from user input)

## Design Principles
1. **Progressive Enhancement**: Works without JavaScript (where possible)
2. **Accessibility**: WCAG 2.1 AA compliance
3. **Performance**: Lazy loading, code splitting
4. **Responsive**: Mobile-first design

## Separation from Backend
- Frontend communicates **only** via HTTP API
- No direct database access
- No shared code with backend (except API contracts)
- Can be deployed separately (CDN, static hosting)

## Development
```bash
# Serve frontend (simple HTTP server)
cd frontend
python -m http.server 8080

# Or with Node.js
npx serve public
```

## Future Enhancements
- Add Vite for HMR and bundling
- TypeScript for type safety
- Tailwind CSS (if requested)
- React/Vue (if complexity warrants it)
