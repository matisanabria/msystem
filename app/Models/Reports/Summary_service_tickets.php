<?php

namespace App\Models\Reports;

use CodeIgniter\Model;

/**
 * Summary_service_tickets
 * Provides data for the service tickets statistics report.
 */
class Summary_service_tickets extends Model
{
    /**
     * Returns ticket count grouped by status.
     * @return array  [ ['status' => '...', 'total' => N], ... ]
     */
    public function getByStatus(): array
    {
        $tickets = $this->db->table($this->db->prefixTable('service_tickets'));
        $tickets->select('status, COUNT(*) AS total');
        $tickets->where('deleted', 0);
        $tickets->groupBy('status');

        return $tickets->get()->getResultArray();
    }

    /**
     * Returns the sum of estimated prices for non-repaired tickets.
     * @return float
     */
    public function getPendingIncome(): float
    {
        $tickets = $this->db->table($this->db->prefixTable('service_tickets'));
        $tickets->selectSum('estimated_price', 'total');
        $tickets->where('deleted', 0);
        $tickets->whereNotIn('status', ['repaired']);

        $row = $tickets->get()->getRowArray();

        return (float) ($row['total'] ?? 0);
    }

    /**
     * Returns ticket counts per technician broken down by status group.
     * @return array
     */
    public function getByTechnician(): array
    {
        $t_tickets = $this->db->prefixTable('service_tickets');
        $t_people  = $this->db->prefixTable('people');

        $sql = "
            SELECT
                CONCAT(p.first_name, ' ', p.last_name) AS technician,
                SUM(CASE WHEN st.status IN ('received', 'waiting', 'in_repair') THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN st.status = 'repaired' THEN 1 ELSE 0 END) AS repaired,
                COUNT(*) AS total
            FROM {$t_tickets} st
            JOIN {$t_people} p ON p.person_id = st.employee_id_technician
            WHERE st.deleted = 0
              AND st.employee_id_technician IS NOT NULL
            GROUP BY st.employee_id_technician
            ORDER BY pending DESC, total DESC
        ";

        return $this->db->query($sql)->getResultArray();
    }

    /**
     * Returns total ticket count by status for KPI summary.
     * @return array  keyed by status
     */
    public function getTotals(): array
    {
        $rows = $this->getByStatus();

        $totals = [
            'received'  => 0,
            'waiting'   => 0,
            'in_repair' => 0,
            'repaired'  => 0,
            'total'     => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status'];
            $count  = (int) $row['total'];

            if (array_key_exists($status, $totals)) {
                $totals[$status] = $count;
            }

            $totals['total'] += $count;
        }

        return $totals;
    }
}
