<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Vehicle extends Model {
    protected $table = 'vehicles';
    protected $primary_key = 'id';
    protected $fillable = ['brand', 'model', 'year', 'plate_number', 'daily_rate', 'status', 'deleted_at'];

    public function __construct()
    {
        parent::__construct();
    }
}

?>
