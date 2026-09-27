<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait InteractsWithModalForms
{
    protected function wantsModalForm(Request $request): bool
    {
        return $request->headers->get('X-Modal') === '1'
            || $request->boolean('modal');
    }
}
