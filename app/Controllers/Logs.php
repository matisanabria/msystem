<?php

namespace App\Controllers;

use App\Models\Activity_log;
use App\Models\Employee;
use App\Models\Stock_location;

require_once('Secure_Controller.php');

class Logs extends Secure_Controller
{
    private Activity_log $activity_log;
    private Stock_location $stock_location;

    public function __construct()
    {
        parent::__construct('logs');

        $this->activity_log  = model(Activity_log::class);
        $this->stock_location = model(Stock_location::class);
    }

    public function getIndex(): void
    {
        $data['employees']      = $this->activity_log->get_employees();
        $data['stock_locations'] = $this->stock_location->get_allowed_locations();

        echo view('logs/manage', $data);
    }

    public function getSearch(): void
    {
        $filters = [
            'log_type'    => $this->request->getGet('log_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
            'employee_id' => (int)($this->request->getGet('employee_id', FILTER_SANITIZE_NUMBER_INT) ?? 0),
            'location_id' => (int)($this->request->getGet('location_id', FILTER_SANITIZE_NUMBER_INT) ?? 0),
            'date_from'   => $this->request->getGet('date_from', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
            'date_to'     => $this->request->getGet('date_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
        ];

        $limit  = (int)($this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT) ?? 50);
        $offset = (int)($this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT) ?? 0);

        $rows  = $this->activity_log->search($filters, $limit, $offset);
        $total = $this->activity_log->get_total($filters);

        echo json_encode(['total' => $total, 'rows' => $rows]);
    }
}
