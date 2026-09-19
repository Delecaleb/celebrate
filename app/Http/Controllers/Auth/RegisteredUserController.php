<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Opening on the visitor's own country saves nearly everyone a tap.
        return view('auth.register', [
            'phoneCountry' => app(\App\Services\LocationModule\LocationService::class)->countryOrFallback(request()->ip()),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => \App\Support\PhoneNumbers::rules(),
        ], [
            // The field builds this itself, so a failure here means an empty or
            // impossible number rather than a format nobody could have guessed.
            'phone.required' => 'Please enter your phone number.',
            'phone.regex'    => 'That phone number does not look right — check the country and the digits.',
        ]);

        $fullname = explode(' ', $request->name, 2);

        $user = User::create([
            'uuid' => str()->uuid(),
            'first_name' => $fullname[0],
            'last_name' => $fullname[1] ?? 'User',
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
