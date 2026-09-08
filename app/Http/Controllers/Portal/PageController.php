<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Domains\Eligibility\RuleSetRegistry;
use Illuminate\Contracts\View\View;

final class PageController
{
    /**
     * Requisitos de acceso.
     *
     * El contenido NO está escrito en la plantilla: se genera desde el conjunto de
     * reglas vigente, el mismo que evalúa las solicitudes. Publicar unos requisitos y
     * aplicar otros no sería un error de contenido, sería un vicio del procedimiento.
     */
    public function requisitos(RuleSetRegistry $registry): View
    {
        $rules = $registry->current();

        return view('portal.requisitos', [
            'requirements' => $rules->describe(),
            'version' => $rules->version(),
            'effectiveFrom' => $rules->effectiveFrom(),
        ]);
    }

    public function accesibilidad(): View
    {
        return view('portal.accesibilidad');
    }

    public function avisoLegal(): View
    {
        return view('portal.aviso-legal');
    }

    public function privacidad(): View
    {
        return view('portal.privacidad');
    }
}
