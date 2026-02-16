<?php

namespace App\Controllers;

/**
 * Tax management disabled - Paraguay IVA 10% is calculated automatically.
 */
class Taxes extends Secure_Controller
{
    public function __construct()
    {
        parent::__construct('taxes');
    }

    public function getIndex(): void
    {
        redirect()->to('home')->send();
    }
}
