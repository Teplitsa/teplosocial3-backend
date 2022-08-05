<?php

namespace Teplosocial\cli;

use Teplosocial\models\{Certificate, Track, Course};

if (!class_exists('WP_CLI')) {
    return;
}

class SetupCertificate
{
}

\WP_CLI::add_command('tps_certificate', '\Teplosocial\cli\SetupCertificate');
