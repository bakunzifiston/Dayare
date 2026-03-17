<?php

namespace App\Http\Controllers;

use App\Models\ClientActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientActivityController extends Controller
{
    public function destroy(Request $request, ClientActivity $client_activity): RedirectResponse
    {
        if (! $request->user()->accessibleBusinessIds()->contains($client_activity->business_id)) {
            abort(404);
        }
        $client = $client_activity->client;
        $client_activity->delete();

        return redirect()->route('clients.show', $client)->with('status', __('Activity deleted.'));
    }
}
