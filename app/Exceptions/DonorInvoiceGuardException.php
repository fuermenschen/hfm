<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an invoice does not satisfy the eligibility rules for a
 * lifecycle action. Bulk orchestration treats it as "skipped" rather
 * than "failed". Messages are user-facing and written in German.
 */
class DonorInvoiceGuardException extends RuntimeException {}
