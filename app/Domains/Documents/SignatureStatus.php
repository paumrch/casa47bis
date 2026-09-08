<?php

declare(strict_types=1);

namespace App\Domains\Documents;

/**
 * Estado de la firma electrónica de un documento. Coincide exactamente con la
 * restricción CHECK `documents_signature_status_check` de la migración.
 *
 * La mayoría de documentos acreditativos (una nómina escaneada, un certificado) no
 * llevan firma electrónica propia: para esos, el estado es `NotApplicable`, que es
 * también el valor por defecto de la columna. `Pending`, `Signed` e `Invalid` sólo
 * tienen sentido para los documentos que sí incorporan una firma a verificar.
 */
enum SignatureStatus: string
{
    /** El documento no lleva firma electrónica: la mayoría de acreditativos. */
    case NotApplicable = 'not_applicable';

    /** Lleva firma pendiente de verificar. */
    case Pending = 'pending';

    /** Firma verificada correctamente. */
    case Signed = 'signed';

    /** La firma no supera la verificación. */
    case Invalid = 'invalid';
}
