<?php

namespace App\Controllers;

/**
 * Tax categories management disabled - Paraguay IVA 10% is calculated automatically.
 */
class Tax_categories extends Secure_Controller
{
    public function __construct()
    {
        parent::__construct('tax_categories');
    }

    public function getIndex(): void
    {
        redirect()->to('home')->send();
    }
}
