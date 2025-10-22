<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class VehiclesController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('Vehicle');
    }

    // GET /vehicles
    public function index() {
        $this->api->require_method('GET');

        $page = $this->api->get_query_params()['page'] ?? 1;
        $limit = $this->api->get_query_params()['limit'] ?? 20;

        $result = $this->Vehicle->paginate((int)$limit, (int)$page);

        $this->api->respond($result);
    }

    // GET /vehicles/{id}
    public function show($id) {
        $this->api->require_method('GET');

        $vehicle = $this->Vehicle->find((int)$id);

        if (!$vehicle) {
            $this->api->respond_error('Vehicle not found', 404);
            return;
        }

        $this->api->respond($vehicle);
    }

    // POST /vehicles
    public function create() {
        $this->api->require_method('POST');

        $input = $this->api->body();

        // Basic validation
        $required = ['brand','model','year','plate_number','daily_rate'];
        foreach ($required as $r) {
            if (empty($input[$r])) {
                $this->api->respond_error($r . ' is required', 400);
                return;
            }
        }

        // Prevent duplicate plate
        if ($this->Vehicle->exists(['plate_number' => $input['plate_number']])) {
            $this->api->respond_error('Vehicle with this plate number already exists', 409);
            return;
        }

        $data = [
            'brand' => $input['brand'],
            'model' => $input['model'],
            'year' => (int)$input['year'],
            'plate_number' => $input['plate_number'],
            'daily_rate' => $input['daily_rate'],
            'status' => $input['status'] ?? 'available',
            'deleted_at' => null
        ];

        $id = $this->Vehicle->insert($data);

        $created = $this->Vehicle->find($id, true);

        $this->api->respond($created, 201);
    }

    // PUT /vehicles/{id}
    public function update($id) {
        $this->api->require_method('PUT');

        $input = $this->api->body();

        $vehicle = $this->Vehicle->find((int)$id);
        if (!$vehicle) {
            $this->api->respond_error('Vehicle not found', 404);
            return;
        }

        // If plate_number changed, ensure uniqueness
        if (!empty($input['plate_number']) && $input['plate_number'] !== $vehicle['plate_number']) {
            if ($this->Vehicle->exists(['plate_number' => $input['plate_number']])) {
                $this->api->respond_error('Another vehicle with this plate number exists', 409);
                return;
            }
        }

        $update = [];
        $fields = ['brand','model','year','plate_number','daily_rate','status','deleted_at'];
        foreach ($fields as $f) {
            if (isset($input[$f])) {
                $update[$f] = $input[$f];
            }
        }

        $this->Vehicle->update((int)$id, $update);

        $updated = $this->Vehicle->find((int)$id, true);

        $this->api->respond($updated);
    }

    // DELETE /vehicles/{id}
    public function delete($id) {
        $this->api->require_method('DELETE');

        $vehicle = $this->Vehicle->find((int)$id);
        if (!$vehicle) {
            $this->api->respond_error('Vehicle not found', 404);
            return;
        }

        // Use soft delete if enabled in config; Model->soft_delete will handle it
        $this->Vehicle->soft_delete((int)$id);

        $this->api->respond(['message' => 'Vehicle deleted']);
    }
}

?>
