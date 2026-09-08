<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

use App\Integrations\Contracts\Values\VerificationOutcome;
use App\Integrations\Contracts\Values\VerificationRequest;

/**
 * Puerto de verificación de datos frente a otras administraciones.
 *
 * En producción lo implementa un adaptador SCSP (SOAP con XML firmado) contra la
 * Plataforma de Intermediación de Datos, alcanzable únicamente a través de Red SARA.
 *
 * POR QUÉ ESTE PUERTO EXISTE Y POR QUÉ TIENE ESTA FORMA
 *
 * 1. El dominio no puede saber que al otro lado hay SOAP. Si mañana la Administración
 *    publica una API REST —y llevan años intentándolo—, se cambia el adaptador y el
 *    dominio no se entera.
 *
 * 2. `unavailable()` no es un caso de error: es un RESULTADO LEGÍTIMO del negocio. Un
 *    procedimiento administrativo no puede detenerse porque un servicio de un tercero
 *    esté caído. Cuando la verificación automática no está disponible, el expediente
 *    pasa a verificación documental asistida por un gestor. Ese camino se prueba igual
 *    que el feliz, porque en la práctica se recorrerá a menudo.
 *
 * 3. Existe un adaptador simulado desde el primer día. Es lo que permite construir y
 *    validar todo el dominio ANTES de tener el alta administrativa en la Plataforma,
 *    que es el cuello de botella real de estos proyectos y suele bloquear meses.
 */
interface DataVerificationGateway
{
    public function verify(VerificationRequest $request): VerificationOutcome;

    /**
     * Consultas que este adaptador puede resolver, para que el dominio sepa qué
     * puede automatizar y qué tiene que pedir en papel.
     *
     * @return list<string>
     */
    public function supportedChecks(): array;
}
