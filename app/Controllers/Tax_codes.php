<?php

namespace App\Controllers;

/**
 * Tax codes management disabled - Paraguay IVA 10% is calculated automatically.
 */
class Tax_codes extends Secure_Controller
{
    public function __construct()
    {
        parent::__construct('tax_codes');
    }

    public function getIndex(): void
    {
        redirect()->to('home')->send();
    }
}
