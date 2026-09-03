<?php

declare(strict_types=1);

namespace App\Enums;

enum DonorInvoiceStatus: string
{
    case RemoteDeleted = 'remote_deleted';
    case Paid = 'paid';
    case Writeoff = 'writeoff';
    case Overdue = 'overdue';
    case PartiallyPaid = 'partially_paid';
    case Sent = 'sent';
    case Created = 'created';
    case NotCreated = 'not_created';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::RemoteDeleted => 'Gelöscht',
            self::Paid => 'Bezahlt',
            self::Writeoff => 'Abgeschrieben',
            self::Overdue => 'Überfällig',
            self::PartiallyPaid => 'Teilbezahlt',
            self::Sent => 'Gesendet',
            self::Created => 'Erstellt',
            self::NotCreated => 'Nicht erstellt',
            self::Unknown => 'Unbekannt',
        };
    }
}
