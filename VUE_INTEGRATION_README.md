# LavaLust Backend + Vue.js Frontend Integration

This project demonstrates how to connect a Vue.js frontend with a LavaLust PHP backend framework using RESTful API endpoints.

## 🚀 Quick Start

### 1. Backend Setup (LavaLust)

The LavaLust backend is already configured with the following features:

- ✅ API endpoints for CRUD operations
- ✅ CORS support for frontend communication
- ✅ JSON response formatting
- ✅ Error handling

#### API Endpoints Available:

```
GET    /api/health           - Health check
GET    /api/config           - Configuration data
GET    /api/items            - Get all items
GET    /api/items/{id}       - Get specific item
POST   /api/items            - Create new item
PUT    /api/items/{id}       - Update item
DELETE /api/items/{id}       - Delete item
```

### 2. Frontend Setup (Vue.js)

The Vue.js frontend is located at: `public/vue-frontend.html`

#### Features:
- ✅ Real-time API connection status
- ✅ Items management (Create, Read, Delete)
- ✅ Responsive design
- ✅ Error handling and user feedback
- ✅ Uses Axios for HTTP requests

### 3. Configuration

#### Update API Base URL

In `public/vue-frontend.html`, update the API base URL to match your setup:

```javascript
apiBaseUrl: 'http://localhost/Web1/api', // Change this to your LavaLust URL
```

Common URLs:
- XAMPP: `http://localhost/Web1/api`
- WAMP: `http://localhost/Web1/api`
- Custom domain: `http://yourdomain.com/api`

#### Enable API in LavaLust

The API is already enabled in `app/config/api.php`:
```php
$config['api_helper_enabled'] = TRUE;
```

### 4. Database Setup (Optional)

To use with a real database instead of sample data:

1. Configure your database in `app/config/database.php`
2. Create tables for your data
3. Update `ApiController.php` to use database queries instead of sample data

Example database configuration:
```php
$database['main'] = array(
    'driver'    => 'mysql',
    'hostname'  => 'localhost',
    'username'  => 'your_username',
    'password'  => 'your_password',
    'database'  => 'your_database',
    'charset'   => 'utf8',
    'dbprefix'  => '',
);
```

### 5. How to Run

1. **Start your web server** (XAMPP, WAMP, etc.)
2. **Access the Vue frontend**: `http://localhost/Web1/public/vue-frontend.html`
3. **Test API endpoints**: The frontend will automatically connect to the backend

### 6. API Usage Examples

#### Using JavaScript/Fetch:
```javascript
// Get all items
fetch('http://localhost/Web1/api/items')
    .then(response => response.json())
    .then(data => console.log(data));

// Create new item
fetch('http://localhost/Web1/api/items', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        name: 'New Item',
        description: 'Item description'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

#### Using cURL:
```bash
# Health check
curl http://localhost/Web1/api/health

# Get items
curl http://localhost/Web1/api/items

# Create item
curl -X POST http://localhost/Web1/api/items \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Item","description":"Test Description"}'

# Delete item
curl -X DELETE http://localhost/Web1/api/items/1
```

## 🛠️ Customization

### Adding Authentication

To add JWT authentication, uncomment and modify the authentication methods in `ApiController.php`:

```php
public function protected_endpoint() {
    // Require JWT token
    $auth = $this->api->require_jwt();
    
    // Your protected code here
    $this->api->respond(['message' => 'Authenticated successfully']);
}
```

### Adding Database Integration

Replace sample data in `ApiController.php` with database queries:

```php
public function index() {
    $this->api->require_method('GET');
    
    // Database query instead of sample data
    $items = $this->db->table('items')->get_all();
    
    $this->api->respond($items);
}
```

### Customizing CORS

Update CORS settings in `app/config/api.php`:

```php
$config['allow_origin'] = 'http://localhost:3000'; // Specific domain
// or
$config['allow_origin'] = '*'; // Allow all origins
```

## 📁 Project Structure

```
Web1/
├── app/
│   ├── config/
│   │   ├── api.php           # API configuration
│   │   └── routes.php        # API routes
│   └── controllers/
│       └── ApiController.php # API controller
├── public/
│   └── vue-frontend.html     # Vue.js frontend
└── README.md                 # This file
```

## 🔧 Troubleshooting

### Common Issues:

1. **CORS Errors**: Check `allow_origin` in `app/config/api.php`
2. **404 Errors**: Verify routes in `app/config/routes.php`
3. **API Not Responding**: Ensure `api_helper_enabled = TRUE` in `app/config/api.php`
4. **Frontend Not Loading**: Check the API base URL in `vue-frontend.html`

### Debug Tips:

1. Check browser console for JavaScript errors
2. Check browser network tab for API request/response
3. Verify web server is running and accessible
4. Test API endpoints directly with cURL or Postman

## 📖 LavaLust Documentation

For more information about LavaLust framework features:
- [Official Documentation](https://lavalust.netlify.app/)
- [API Library](https://lavalust.netlify.app/libraries/api)
- [Routing](https://lavalust.netlify.app/core_topics/routes)

## 🎯 Next Steps

1. Add user authentication with JWT tokens
2. Integrate with a real database
3. Add form validation
4. Implement file upload functionality
5. Add real-time features with WebSockets
6. Deploy to production server

This setup provides a solid foundation for building modern web applications with LavaLust backend and Vue.js frontend!