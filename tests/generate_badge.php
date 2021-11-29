<?php
/**
 * dblmetaphone
 *
 * @package     dblmetaphone
 * @author      Adrian Green
 * @copyright   Copyright (c) 2021
 */
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

$command = new PhpUnitCoverageBadge\Command(
    new PhpUnitCoverageBadge\ReportParser\CloverReportParser(), new PhpUnitCoverageBadge\BadgeGenerator()
);
$command->run();

