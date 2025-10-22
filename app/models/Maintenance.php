<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Maintenance extends Model {
    protected $table = 'maintenance';
    protected $primary_key = 'id';
    protected $fillable = ['vehicle_id', 'description', 'scheduled_date', 'cost', 'status', 'deleted_at'];

    public function __construct()
    {
        parent::__construct();
    }
}

?>