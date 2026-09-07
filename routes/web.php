<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is the main entry point for the application's web routes.
| Each business module has been split into its own file under this
| same routes/ directory to keep things maintainable. Add new modules
| by creating a routes/<module>.php file and requiring it below.
|
*/

// Core application routes (welcome page, career applications,
// authenticated dashboard/profile, employee UI, trainee portal)
require __DIR__.'/core.php';

// Human Resources Management
require __DIR__.'/hrm.php';

// Workforce Management (scheduling, leave, absences)
require __DIR__.'/workforce.php';

// Supply Chain Management
require __DIR__.'/scm.php';

// Financial Operations
require __DIR__.'/fin.php';

// Manufacturing Plant
require __DIR__.'/man.php';

// Warehouse
require __DIR__.'/warehouse.php';

// Inventory
require __DIR__.'/inventory.php';

// Order Processing
require __DIR__.'/ord.php';

// Logistics
require __DIR__.'/log.php';

// Customer Relationship Management
require __DIR__.'/crm.php';

// E-Commerce
require __DIR__.'/eco.php';

// Procurement
require __DIR__.'/pro.php';

// Project Automation
require __DIR__.'/proj.php';

// IT & Systems Admin
require __DIR__.'/it.php';

// CEO Dashboard
require __DIR__.'/ceo.php';

// B2B Client Gateway (auth + partner portal)
require __DIR__.'/client.php';

// Vendor/Supplier Gateway (auth + supplier portal)
require __DIR__.'/supplier.php';

/*
|--------------------------------------------------------------------------
| Framework Generated Auth Routes (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';