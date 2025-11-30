<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class VehiclesController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('Vehicle');
        $this->call->model('Booking');
        $this->call->model('Maintenance');
    }

    // GET /vehicles
    public function index() {
        $this->api->require_method('GET');

        try {
            // Get all GET parameters if any exist
            $getAllParams = !empty($_GET) ? $this->io->get() : [];
            $filters = [
                'status' => isset($getAllParams['status']) ? $getAllParams['status'] : null,
                'search' => isset($getAllParams['search']) ? $getAllParams['search'] : null,
                'start_date' => isset($getAllParams['start_date']) ? $getAllParams['start_date'] : null,
                'end_date' => isset($getAllParams['end_date']) ? $getAllParams['end_date'] : null
            ];
            
            $vehicles = $this->Vehicle->getAllVehicles($filters);
            
            // If dates are provided, check availability for each vehicle
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $vehicles = array_map(function($vehicle) use ($filters) {
                    $vehicle['is_available'] = $this->Booking->checkVehicleAvailability(
                        $vehicle['id'], 
                        $filters['start_date'], 
                        $filters['end_date']
                    );
                    return $vehicle;
                }, $vehicles);
            }
            
            $this->api->respond([
                'vehicles' => $vehicles,
                'total' => count($vehicles)
            ]);
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->api->respond_error('Failed to fetch vehicles', 500);
        }
    }

    // GET /vehicles/{id}
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            // Find vehicle
            $vehicle = $this->Vehicle->getVehicleById($id);
            
            if (!$vehicle) {
                return $this->api->respond_error('Vehicle not found', 404);
            }
            
            $this->api->respond($vehicle);
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->api->respond_error('Failed to fetch vehicle', 500);
        }
    }

    // POST /vehicles
    public function create() {
        $this->api->require_method('POST');
        
        try {
            // Get form data from $_POST (multipart/form-data)
            $input = $_POST;
            
            // Handle image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['image'];
                
                // Validate file upload
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('File upload error');
                }
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed');
                }
                
                // Validate file size (max 5MB)
                $maxSize = 5 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    throw new Exception('File size exceeds maximum limit of 5MB');
                }
                
                // Prepare upload directory
                $uploadDir = 'public/images/vehicles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'vehicle_' . time() . '_' . uniqid() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;
                
                // Move uploaded file
                if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    throw new Exception('Failed to save uploaded file');
                }
                
                // Add image path to input data (store without 'public/' prefix for frontend)
                $input['image'] = str_replace('public/', '', $uploadPath);
            }
            
            // Create vehicle (includes validation)
            $this->Vehicle->createVehicle($input);
            $id = $this->db->last_id();
            
            // Fetch the created record
            $created = $this->Vehicle->getVehicleById($id);
            
            $this->api->respond($created, 201);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 400);
        }
    }


    // PUT /vehicles/{id}
    public function update($id) {
        // Allow both PUT and POST for updates (POST needed for file uploads)
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->api->respond_error("Method Not Allowed", 405);
        }
        
        try {
            // Get form data - try multiple sources
            $input = [];
            
            // First try $_POST
            if (!empty($_POST)) {
                $input = $_POST;
            }
            
            // If $_POST is empty, try to get from body
            if (empty($input)) {
                $body = $this->api->body();
                if (!empty($body)) {
                    $input = $body;
                }
            }
            
            // Filter out only the fields we want to update
            $allowedFields = ['brand', 'model', 'year', 'plate_number', 'daily_rate', 'status'];
            $input = array_filter($input, function($key) use ($allowedFields) {
                return in_array($key, $allowedFields);
            }, ARRAY_FILTER_USE_KEY);
            
            // Verify we have data
            if (empty($input) && empty($_FILES['image'])) {
                throw new Exception('No data provided for update');
            }
            
            // Handle image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                error_log("=== IMAGE UPLOAD DETECTED IN UPDATE ===");
                error_log("File info: " . json_encode($_FILES['image']));
                
                $file = $_FILES['image'];
                
                // Validate file upload
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    error_log("File upload error code: " . $file['error']);
                    throw new Exception('File upload error');
                }
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed');
                }
                
                // Validate file size (max 5MB)
                $maxSize = 5 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    throw new Exception('File size exceeds maximum limit of 5MB');
                }
                
                // Prepare upload directory
                $uploadDir = 'public/images/vehicles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Get old vehicle to potentially delete old image
                $oldVehicle = $this->Vehicle->getVehicleById($id);
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'vehicle_' . time() . '_' . uniqid() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;
                
                // Move uploaded file
                if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    throw new Exception('Failed to save uploaded file');
                }
                
                // Delete old image if it exists (need to add 'public/' prefix for actual file path)
                if ($oldVehicle && !empty($oldVehicle['image'])) {
                    $oldImagePath = 'public/' . $oldVehicle['image'];
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }
                
                // Add image path to input data (store without 'public/' prefix for frontend)
                $input['image'] = str_replace('public/', '', $uploadPath);
                error_log("Image uploaded successfully to: " . $uploadPath);
            }
            
            // Debug: log what we're updating
            error_log("Updating vehicle $id with data: " . json_encode($input));
            
            // Update vehicle (includes validation)
            $this->Vehicle->updateVehicle($id, $input);
            
            // Fetch updated record
            $updated = $this->Vehicle->getVehicleById($id);
            
            $this->api->respond($updated);
            
        } catch (Exception $e) {
            // Handle different error types
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->api->respond_error($e->getMessage(), 404);
            } else {
                $this->api->respond_error($e->getMessage(), 400);
            }
        }
    }

    // DELETE /vehicles/{id}
    public function delete($id) {
        $this->api->require_method('DELETE');

        try {
            // Soft delete vehicle
            $this->Vehicle->deleteVehicle($id);
            
            $this->api->respond(['message' => 'Vehicle deleted successfully']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->api->respond_error($e->getMessage(), 404);
            } else {
                $this->api->respond_error($e->getMessage(), 400);
            }
        }
    }
    
    // GET /vehicles/{id}/booked-dates
    public function bookedDates($id) {
        $this->api->require_method('GET');
        
        try {
            $bookedDates = $this->Vehicle->getBookedDates($id);
            $maintenanceDates = $this->Vehicle->getMaintenanceDates($id);
            
            $this->api->respond([
                'booked_dates' => $bookedDates,
                'maintenance_dates' => $maintenanceDates
            ]);
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->api->respond_error('Failed to fetch booked dates', 500);
        }
    }
}

?>
