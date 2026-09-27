<?php

namespace App\Http\Requests\Concerns;

trait RedirectsToModalQuery
{
    /**
     * Named route + parameters used when validation fails for modal forms.
     *
     * @return array{0: string, 1?: array<string, mixed>}
     */
    abstract protected function modalFallbackRoute(): array;

    protected function getRedirectUrl(): string
    {
        [$name, $parameters] = array_pad($this->modalFallbackRoute(), 2, []);

        return $this->redirector->getUrlGenerator()->route($name, $parameters ?? []);
    }
}
