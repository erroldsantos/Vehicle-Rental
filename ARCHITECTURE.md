# Architecture Diagram: LavaLust + Vue Integration

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          USER BROWSER                                    │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    Vue.js Frontend                               │   │
│  │                   (http://localhost:5173)                        │   │
│  │                                                                   │   │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐                │   │
│  │  │ Components │  │   Router   │  │   Stores   │                │   │
│  │  │   .vue     │  │   routes   │  │  (Pinia)   │                │   │
│  │  └────────────┘  └────────────┘  └────────────┘                │   │
│  │         │               │               │                        │   │
│  │         └───────────────┴───────────────┘                        │   │
│  │                         │                                         │   │
│  │                  ┌──────▼──────┐                                │   │
│  │                  │  API Store  │  ← Centralized API client       │   │
│  │                  │  (api.js)   │                                │   │
│  │                  └──────┬──────┘                                │   │
│  │                         │                                         │   │
│  │                  ┌──────▼──────┐                                │   │
│  │                  │    Axios    │  ← HTTP client                  │   │
│  │                  └──────┬──────┘                                │   │
│  └─────────────────────────┼────────────────────────────────────────┘   │
│                             │                                             │
└─────────────────────────────┼─────────────────────────────────────────────┘
                              │
                              │ HTTP Request: /api/vehicles
                              │ Proxy: Vite Dev Server
                              │
┌─────────────────────────────▼─────────────────────────────────────────────┐
│                          WEB SERVER                                        │
│                     (XAMPP / Apache + PHP)                                 │
│                 http://localhost/Vehicle-Rental                            │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐  │
│  │                    LavaLust Framework                                │  │
│  │                                                                       │  │
│  │  ┌──────────────┐                                                   │  │
│  │  │   Router     │  ← Routes defined in config/routes.php           │  │
│  │  │  (routes.php)│                                                   │  │
│  │  └──────┬───────┘                                                   │  │
│  │         │                                                            │  │
│  │         │ /api/vehicles → VehiclesController::index                 │  │
│  │         │                                                            │  │
│  │  ┌──────▼───────────────────────────────────┐                      │  │
│  │  │      VehiclesController                   │                      │  │
│  │  │  extends BaseApiController                │                      │  │
│  │  │                                            │                      │  │
│  │  │  ┌──────────────────────────────────┐   │                      │  │
│  │  │  │  public function index() {       │   │                      │  │
│  │  │  │    $vehicles = $this->db->query()│   │                      │  │
│  │  │  │    $this->success($vehicles)     │   │                      │  │
│  │  │  │  }                                │   │                      │  │
│  │  │  └──────────────────────────────────┘   │                      │  │
│  │  │                 │           │             │                      │  │
│  │  │                 │           │             │                      │  │
│  │  │            ┌────▼───┐  ┌───▼──────┐     │                      │  │
│  │  │            │ $this  │  │  $this   │     │                      │  │
│  │  │            │  ->db  │  │  ->api   │     │                      │  │
│  │  │            └────┬───┘  └───┬──────┘     │                      │  │
│  │  └─────────────────┼──────────┼─────────────┘                      │  │
│  │                    │          │                                     │  │
│  │  ┌─────────────────▼──────┐  │                                     │  │
│  │  │  Database.php          │  │                                     │  │
│  │  │  (Singleton Helper)    │  │                                     │  │
│  │  │                        │  │                                     │  │
│  │  │  - query()             │  │                                     │  │
│  │  │  - queryOne()          │  │                                     │  │
│  │  │  - execute()           │  │                                     │  │
│  │  │  - lastInsertId()      │  │                                     │  │
│  │  └────────────┬───────────┘  │                                     │  │
│  │               │                │                                     │  │
│  │  ┌────────────▼───────────┐  │                                     │  │
│  │  │   PDO Connection       │  │                                     │  │
│  │  │  (Single Instance)     │  │                                     │  │
│  │  └────────────┬───────────┘  │                                     │  │
│  │               │                │                                     │  │
│  └───────────────┼────────────────┼─────────────────────────────────────┘  │
│                  │                │                                         │
│  ┌───────────────▼───────────┐   │                                         │
│  │   MySQL Database          │   │                                         │
│  │   vehicle_rental          │   │                                         │
│  │                           │   │                                         │
│  │   Tables:                 │   │                                         │
│  │   - vehicles              │   │                                         │
│  │   - users                 │   │                                         │
│  │   - bookings              │   │                                         │
│  │   - payments              │   │                                         │
│  │   - maintenance           │   │                                         │
│  └───────────────┬───────────┘   │                                         │
│                  │                 │                                         │
│  ◄───────SQL────┘                 │                                         │
│                                    │                                         │
│  ┌─────────────────────────────────▼─────────────┐                         │
│  │         LavaLust API Library                   │                         │
│  │                                                 │                         │
│  │  - handle_cors()       ← CORS headers         │                         │
│  │  - respond()           ← JSON response        │                         │
│  │  - respond_error()     ← Error response       │                         │
│  │  - body()              ← Parse request body   │                         │
│  │  - require_method()    ← HTTP method check    │                         │
│  └─────────────────────────────────────────────────┘                         │
│                                                                               │
└───────────────────────────────────────────────────────────────────────────────┘
```

## 🔄 Request Flow Example

### Example: Fetching All Vehicles

```
1. User clicks "View Vehicles" in Vue app
   ↓
2. Vue component calls:
   const vehicles = await apiStore.get('/vehicles')
   ↓
3. Axios sends HTTP GET to:
   http://localhost:5173/api/vehicles
   ↓
4. Vite proxy forwards to:
   http://localhost/Vehicle-Rental/api/vehicles
   ↓
5. LavaLust Router matches:
   /api/vehicles → VehiclesController::index
   ↓
6. VehiclesController extends BaseApiController
   - Has $this->db (Database singleton)
   - Has $this->api (API library)
   ↓
7. Controller executes:
   $vehicles = $this->db->query("SELECT * FROM vehicles WHERE deleted_at IS NULL")
   ↓
8. Database.php uses PDO:
   - Single connection (reused)
   - Prepared statement
   - Returns array of vehicles
   ↓
9. Controller responds:
   $this->success(['vehicles' => $vehicles])
   ↓
10. API library sets:
    - Content-Type: application/json
    - CORS headers
    - HTTP status 200
    ↓
11. JSON response sent back:
    {
      "data": {
        "vehicles": [...],
        "total": 10
      }
    }
    ↓
12. Axios receives response in Vue
    ↓
13. Component updates UI with vehicles
```

## 📦 File Structure

```
Vehicle-Rental/
│
├── frontend/                          ← Vue.js Frontend
│   ├── src/
│   │   ├── components/               ← Reusable Vue components
│   │   ├── views/                    ← Page components
│   │   ├── router/                   ← Vue Router config
│   │   ├── stores/
│   │   │   └── api.js                ← ⭐ API Store (Centralized HTTP)
│   │   ├── App.vue
│   │   └── main.js
│   ├── vite.config.js                ← ⭐ Proxy config
│   └── package.json
│
├── app/                               ← LavaLust Backend
│   ├── config/
│   │   ├── database.php              ← ⭐ DB credentials
│   │   ├── api.php                   ← ⭐ API settings
│   │   └── routes.php                ← ⭐ API routes
│   │
│   ├── controllers/
│   │   ├── BaseApiController.php     ← ⭐ Base for all API controllers
│   │   ├── VehiclesController.php    ← Example controller
│   │   ├── UsersController.php
│   │   └── BookingsController.php
│   │
│   ├── helpers/
│   │   └── Database.php              ← ⭐ PDO wrapper singleton
│   │
│   └── models/
│       ├── Vehicle.php
│       └── User.php
│
├── scheme/
│   └── database/
│       └── full_schema.sql           ← Database schema
│
├── SIMPLIFIED_INTEGRATION.md         ← ⭐ Main guide
├── QUICK_REFERENCE.md                ← ⭐ Cheat sheet
└── BEFORE_AFTER.md                   ← ⭐ Code comparison
```

## 🎯 Key Concepts

### 1. **Single Database Connection**
```php
// OLD: New connection every method
$pdo = new PDO(...)  // ← Repeat 10 times!

// NEW: Singleton pattern
$this->db  // ← Same instance everywhere
```

### 2. **Vite Dev Proxy (No CORS Issues)**
```javascript
// Development: Vite proxies /api to backend
fetch('/api/vehicles')  // → http://localhost/Vehicle-Rental/api/vehicles

// Production: Direct calls
fetch('https://api.yoursite.com/vehicles')
```

### 3. **Inheritance Hierarchy**
```
Controller (LavaLust core)
    ↓
BaseApiController (your base)
    ↓
VehiclesController (your feature)
```

### 4. **Centralized Error Handling**
```php
// Consistent error responses
try {
    $this->db->query(...)
} catch (Exception $e) {
    $this->handleDbError($e)  // ← Logs & sends user-friendly error
}
```

## 🚀 Development vs Production

### Development
```
Vue Dev Server (5173) → Vite Proxy → LavaLust (XAMPP)
- Hot reload
- Source maps
- No CORS issues (proxy)
```

### Production
```
Build Vue → Deploy static files → Serve from same domain as API
- Minified bundle
- No proxy needed
- Same origin = no CORS
```

Build command:
```bash
cd frontend
npm run build
# Outputs to frontend/dist/
# Copy to LavaLust public/ folder
```

---

**Visual learner?** Print this diagram and keep it handy while coding! 🖨️
