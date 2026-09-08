<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Domains\Housing\Models\Property;
use App\Http\Queries\PropertyFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Catálogo público de viviendas.
 *
 * Devuelve HTML completo. El contenido llega en la primera respuesta, así que se ve con
 * JavaScript desactivado, lo indexa un buscador, lo lee un lector de pantalla sin
 * esperar a nada y aparece en pantalla en cuanto llegan los bytes.
 */
final class PropertyController
{
    private const POR_PAGINA = 12;

    public function index(Request $request): View
    {
        $filters = PropertyFilters::fromRequest($request);

        $query = Property::query()->available()->with('development');

        if ($filters->search !== null) {
            $query->search($filters->search);
        }

        if ($filters->municipality !== null) {
            $query->inMunicipality($filters->municipality);
        }

        if ($filters->bedrooms !== null) {
            $query->withMinimumBedrooms($filters->bedrooms);
        }

        if ($filters->maxRent !== null) {
            $query->withMaxRent($filters->maxRent);
        }

        if ($filters->accessibleOnly) {
            $query->accessible();
        }

        $properties = $query
            ->orderBy('monthly_rent')
            ->orderBy('reference_code')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('portal.viviendas.index', [
            'properties' => $properties,
            'filters' => $filters,
            'municipalities' => $this->municipalities(),
        ]);
    }

    public function show(Property $property): View
    {
        return view('portal.viviendas.show', [
            'property' => $property->load('development', 'media'),
        ]);
    }

    /**
     * Municipios con viviendas disponibles.
     *
     * Se consulta en vez de mantener una lista: si una lista fija se queda obsoleta, el
     * desplegable ofrece municipios sin resultados, que es la forma más rápida de que
     * alguien concluya que el portal no funciona.
     *
     * @return array<int, string>
     */
    private function municipalities(): array
    {
        return Property::query()
            ->available()
            ->distinct()
            ->orderBy('municipality')
            ->pluck('municipality')
            ->map(static fn (mixed $name): string => (string) $name)
            ->values()
            ->all();
    }
}
