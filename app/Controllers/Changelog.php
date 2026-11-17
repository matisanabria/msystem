<?php

namespace App\Controllers;

/**
 * Customer-facing list of changes. Entries live in app/Data/changelog.php.
 */
class Changelog extends Secure_Controller
{
    public function __construct()
    {
        parent::__construct('changelog', null, 'office');
    }

    public function getIndex(): void
    {
        $entries = include APPPATH . 'Data/changelog.php';

        usort($entries, static fn (array $a, array $b): int => strcmp($b['date'], $a['date']));

        $by_month = [];
        foreach ($entries as $entry) {
            $by_month[substr($entry['date'], 0, 7)][] = $entry;
        }

        echo view('changelog/index', ['months' => $by_month]);
    }
}
