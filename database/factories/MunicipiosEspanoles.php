<?php

declare(strict_types=1);

namespace Database\Factories;

/**
 * Municipios y provincias españolas reales, para que los datos de
 * demostración sean verosímiles sin depender del locale de Faker.
 */
final class MunicipiosEspanoles
{
    /**
     * @var list<array{municipio: string, provincia: string}>
     */
    public const TODOS = [
        ['municipio' => 'Madrid', 'provincia' => 'Madrid'],
        ['municipio' => 'Alcalá de Henares', 'provincia' => 'Madrid'],
        ['municipio' => 'Getafe', 'provincia' => 'Madrid'],
        ['municipio' => 'Barcelona', 'provincia' => 'Barcelona'],
        ['municipio' => 'Badalona', 'provincia' => 'Barcelona'],
        ['municipio' => 'Sabadell', 'provincia' => 'Barcelona'],
        ['municipio' => 'Valencia', 'provincia' => 'Valencia'],
        ['municipio' => 'Gandía', 'provincia' => 'Valencia'],
        ['municipio' => 'Sevilla', 'provincia' => 'Sevilla'],
        ['municipio' => 'Dos Hermanas', 'provincia' => 'Sevilla'],
        ['municipio' => 'Zaragoza', 'provincia' => 'Zaragoza'],
        ['municipio' => 'Valladolid', 'provincia' => 'Valladolid'],
        ['municipio' => 'Málaga', 'provincia' => 'Málaga'],
        ['municipio' => 'Marbella', 'provincia' => 'Málaga'],
        ['municipio' => 'Murcia', 'provincia' => 'Murcia'],
        ['municipio' => 'Cartagena', 'provincia' => 'Murcia'],
        ['municipio' => 'Bilbao', 'provincia' => 'Vizcaya'],
        ['municipio' => 'Vitoria-Gasteiz', 'provincia' => 'Álava'],
        ['municipio' => 'A Coruña', 'provincia' => 'A Coruña'],
        ['municipio' => 'Santiago de Compostela', 'provincia' => 'A Coruña'],
        ['municipio' => 'Oviedo', 'provincia' => 'Asturias'],
        ['municipio' => 'Gijón', 'provincia' => 'Asturias'],
        ['municipio' => 'Santander', 'provincia' => 'Cantabria'],
        ['municipio' => 'Logroño', 'provincia' => 'La Rioja'],
        ['municipio' => 'Pamplona', 'provincia' => 'Navarra'],
        ['municipio' => 'San Sebastián', 'provincia' => 'Guipúzcoa'],
        ['municipio' => 'Toledo', 'provincia' => 'Toledo'],
        ['municipio' => 'Talavera de la Reina', 'provincia' => 'Toledo'],
        ['municipio' => 'Albacete', 'provincia' => 'Albacete'],
        ['municipio' => 'Ciudad Real', 'provincia' => 'Ciudad Real'],
        ['municipio' => 'Badajoz', 'provincia' => 'Badajoz'],
        ['municipio' => 'Cáceres', 'provincia' => 'Cáceres'],
        ['municipio' => 'Salamanca', 'provincia' => 'Salamanca'],
        ['municipio' => 'Burgos', 'provincia' => 'Burgos'],
        ['municipio' => 'León', 'provincia' => 'León'],
        ['municipio' => 'Granada', 'provincia' => 'Granada'],
        ['municipio' => 'Córdoba', 'provincia' => 'Córdoba'],
        ['municipio' => 'Almería', 'provincia' => 'Almería'],
        ['municipio' => 'Jerez de la Frontera', 'provincia' => 'Cádiz'],
        ['municipio' => 'Cádiz', 'provincia' => 'Cádiz'],
        ['municipio' => 'Alicante', 'provincia' => 'Alicante'],
        ['municipio' => 'Elche', 'provincia' => 'Alicante'],
        ['municipio' => 'Castellón de la Plana', 'provincia' => 'Castellón'],
        ['municipio' => 'Palma', 'provincia' => 'Islas Baleares'],
        ['municipio' => 'Las Palmas de Gran Canaria', 'provincia' => 'Las Palmas'],
        ['municipio' => 'Santa Cruz de Tenerife', 'provincia' => 'Santa Cruz de Tenerife'],
    ];
}
