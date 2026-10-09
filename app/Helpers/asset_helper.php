<?php

if (!function_exists('asset_v')) {
    /**
     * Adds the file modification time to a public asset URL ("css/login.css?v=1760000000") so Cloudflare and
     * the browser fetch the new file after a deploy. Meant for assets that have no hash in their name.
     *
     * @param string $path Path relative to /public, e.g. "resources/bootswatch/flatly/bootstrap.min.css"
     * @return string
     */
    function asset_v(string $path): string
    {
        $file = FCPATH . $path;

        return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
    }
}
