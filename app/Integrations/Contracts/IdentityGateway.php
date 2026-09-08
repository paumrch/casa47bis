<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

/**
 * Puerto de identidad del ciudadano.
 *
 * En producción lo implementa un adaptador Cl@ve por SAML 2.0. Cl@ve se consume por
 * Internet público mediante redirección del navegador del ciudadano: no requiere
 * Red SARA. Es, con diferencia, la integración más sencilla de todas, y es
 * exactamente la función que una plataforma propietaria factura como valor añadido.
 */
interface IdentityGateway
{
    /** URL a la que redirigir al ciudadano para que se autentique. */
    public function authenticationUrl(string $returnUrl, string $state): string;

    /** Valida la respuesta del proveedor y devuelve la identidad acreditada. */
    public function verifyAssertion(string $rawAssertion): Values\VerifiedIdentity;
}
