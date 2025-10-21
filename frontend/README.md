# Vue.js Admin Dashboard for LavaLust

This is a modern Vue.js 3 admin dashboard that connects to the LavaLust PHP backend framework.

## 🚀 Features

- ✅ **Modern Vue.js 3** with Composition API
- ✅ **Component-Based Architecture** - Proper .vue Single File Components
- ✅ **Vue Router** for navigation
- ✅ **Pinia** for state management
- ✅ **Vite** for fast development and building
- ✅ **Responsive Design** - Works on desktop and mobile
- ✅ **Real-time API Integration** with LavaLust backend
- ✅ **Charts and Analytics** with Chart.js
- ✅ **User Management** - CRUD operations
- ✅ **Dashboard Statistics** - Live data from backend

## 📁 Project Structure

```
frontend/
├── src/
│   ├── components/          # Reusable Vue components
│   │   ├── Sidebar.vue      # Navigation sidebar
│   │   ├── Header.vue       # Top header bar
│   │   ├── StatsCard.vue    # Statistics card component
│   │   └── AlertMessage.vue # Alert/notification component
│   ├── views/               # Page components
│   │   ├── Dashboard.vue    # Main dashboard view
│   │   ├── UserManagement.vue # User management page
│   │   ├── ItemManagement.vue # Item management page
│   │   ├── Analytics.vue    # Charts and analytics
│   │   └── Settings.vue     # Settings page
│   ├── stores/              # Pinia stores
│   │   └── api.js           # API service store
│   ├── router/              # Vue Router config
│   │   └── index.js         # Routes configuration
│   ├── App.vue              # Main app component
│   ├── main.js              # App entry point
│   └── style.css            # Global styles
├── package.json             # Dependencies
├── vite.config.js           # Vite configuration
└── index.html               # HTML template
```

## 🛠️ Setup Instructions

### Prerequisites
- Node.js (version 16 or higher)
- npm or yarn package manager
- LavaLust backend running on localhost:3000

### Installation

1. **Navigate to frontend directory:**
   ```bash
   cd C:\xampp\htdocs\Web1\frontend
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Start development server:**
   ```bash
   npm run dev
   ```

4. **Access the dashboard:**
   Open your browser and go to: `http://localhost:5173`

### Build for Production

```bash
npm run build
```

This creates a `dist/` folder with optimized files ready for deployment.

## 🔌 API Integration

The frontend automatically connects to the LavaLust backend API:

- **Backend URL**: `http://localhost:3000/api`
- **Proxy Configuration**: Vite proxies `/api` requests to the backend
- **Auto-reconnection**: Dashboard shows connection status

### Available Endpoints:

- `GET /api/health` - Health check
- `GET /api/admin/stats` - Dashboard statistics
- `GET /api/admin/users` - User management
- `POST /api/admin/users` - Create user
- `DELETE /api/admin/users/{id}` - Delete user
- `GET /api/items` - Item management
- `GET /api/admin/analytics` - Analytics data

## 🎨 Key Differences from HTML Version

### **HTML Version (.html)**
- ❌ Single large file
- ❌ No component separation
- ❌ No build process
- ❌ Hard to maintain
- ❌ No hot reload
- ✅ Works directly in browser

### **Vue.js Version (.vue)**
- ✅ Modular components
- ✅ Proper separation of concerns
- ✅ Build optimization
- ✅ Easy to maintain and extend
- ✅ Hot reload development
- ✅ Production-ready
- ✅ TypeScript support (optional)

## 🔧 Configuration

### API Base URL
Update in `src/stores/api.js`:
```javascript
state: () => ({
  baseUrl: 'http://localhost:3000/api', // Change this
})
```

### Vite Proxy
Update in `vite.config.js`:
```javascript
server: {
  proxy: {
    '/api': {
      target: 'http://localhost:3000', // Change this
      changeOrigin: true
    }
  }
}
```

## 📱 Responsive Design

The dashboard works perfectly on:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

Mobile features:
- Collapsible sidebar
- Touch-friendly navigation
- Responsive tables
- Mobile-optimized forms

## 🚀 Development vs Production

### Development Mode
```bash
npm run dev
```
- Hot reload
- Source maps
- Unminified code
- Development tools

### Production Mode
```bash
npm run build
npm run preview
```
- Minified code
- Optimized assets
- Tree shaking
- Code splitting

## 🔄 Why This Approach?

**Professional Development:**
- Industry standard Vue.js setup
- Scalable architecture
- Easy team collaboration
- Better debugging tools

**Performance:**
- Code splitting
- Lazy loading
- Optimized builds
- Smaller bundle sizes

**Maintainability:**
- Component reusability
- Clear file structure
- Separation of concerns
- Easy testing

## 🎯 Next Steps

1. **Run the backend:** Make sure LavaLust is running on localhost:3000
2. **Install dependencies:** Run `npm install` in frontend directory
3. **Start development:** Run `npm run dev`
4. **Access dashboard:** Open http://localhost:5173

The Vue.js dashboard will automatically connect to your LavaLust backend and provide a modern, professional admin interface! 🎉