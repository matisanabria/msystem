<?php

/**
 * PDF helper
 */
function create_pdf(string $html, string $filename = ''): string
{
    // isPhpEnabled stays off: it would run PHP embedded in the HTML. No view uses <script type="text/php">.
    $dompdf = new Dompdf\Dompdf(['isRemoteEnabled' => true, 'isPhpEnabled' => false]);
    $dompdf->loadHtml(str_replace(['\n', '\r'], '', $html));
    $dompdf->render();

    if ($filename != '') {
        $dompdf->stream($filename . '.pdf');
    } else {    // TODO: Not all paths return a value.
        return $dompdf->output();
    }

    return '';
}
