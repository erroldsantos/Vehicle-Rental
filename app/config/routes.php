<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
| -------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------
| Here is where you can register web routes for your application.
|
|
*/

// Default route
$router->get('/', 'Welcome::index');

// API Routes for Vue Frontend Integration
$router->group('/api', function() use ($router) {
    
    // Health check endpoint
    $router->get('/health', 'ApiController::health');
    
    // Configuration endpoint
    $router->get('/config', 'ApiController::config');
    
    // Vehicle Management API endpoints
    $router->get('/vehicles', 'VehiclesController::index');
    $router->get('/vehicles/{id}', 'VehiclesController::show');
    $router->post('/vehicles', 'VehiclesController::create');
    $router->put('/vehicles/{id}', 'VehiclesController::update');
    $router->delete('/vehicles/{id}', 'VehiclesController::delete');
    
    // Maintenance Management API endpoints
    $router->get('/maintenance', 'MaintenanceController::index');
    $router->get('/maintenance/vehicles', 'MaintenanceController::vehicles');
    $router->post('/maintenance/sync', 'MaintenanceController::sync');
    $router->get('/maintenance/{id}', 'MaintenanceController::show');
    $router->post('/maintenance', 'MaintenanceController::create');
    $router->put('/maintenance/{id}', 'MaintenanceController::update');
    $router->put('/maintenance/{id}/complete', 'MaintenanceController::complete');
    $router->delete('/maintenance/{id}', 'MaintenanceController::delete');
    
    // User Management API endpoints
    $router->get('/users', 'UsersController::index');
    $router->get('/users/{id}', 'UsersController::show');
    $router->post('/users', 'UsersController::create');
    $router->put('/users/{id}', 'UsersController::update');
    $router->delete('/users/{id}', 'UsersController::delete');
    $router->post('/users/login', 'UsersController::login');
    
    // Booking Management API endpoints
    $router->get('/bookings', 'BookingsController::index');
    $router->get('/bookings/available-vehicles', 'BookingsController::availableVehicles');
    $router->get('/bookings/users', 'BookingsController::users');
    $router->get('/bookings/{id}', 'BookingsController::show');
    $router->post('/bookings', 'BookingsController::create');
    $router->put('/bookings/{id}', 'BookingsController::update');
    $router->put('/bookings/{id}/cancel', 'BookingsController::cancel');
    $router->delete('/bookings/{id}', 'BookingsController::delete');
    
    // Authentication API endpoints
    $router->post('/auth/login', 'AuthController::login');
    $router->post('/auth/logout', 'AuthController::logout');
    $router->get('/auth/me', 'AuthController::me');
    $router->post('/auth/forgot-password', 'AuthController::forgotPassword');
    
    // Admin Dashboard API endpoints
    $router->get('/admin/stats', 'AdminController::stats');
    $router->get('/admin/overview', 'AdminController::overview');
    $router->get('/admin/users', 'AdminController::users');
    $router->post('/admin/users', 'AdminController::create_user');
    $router->put('/admin/users/{id}', 'AdminController::update_user');
    $router->delete('/admin/users/{id}', 'AdminController::delete_user');
    $router->get('/admin/logs', 'AdminController::logs');
    $router->get('/admin/analytics', 'AdminController::analytics');
    $router->get('/admin/settings', 'AdminController::settings');
    $router->post('/admin/settings', 'AdminController::settings');
    $router->get('/admin/export', 'AdminController::export');
    
});