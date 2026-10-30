<?php

namespace Website\Modules\Contact;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Website\Modules\Pages\Page;

class ContactController extends Controller
{
    public function show()
    {
        return view('website::site.contact.show', ['page' => Page::where('slug', 'contact')->first()]);
    }

    public function submit(Request $request)
    {
        if ($request->filled('website')) {
            return redirect()->route('website.contact')->with('contact_ok', true);
        }
        $key = 'site-contact:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput()->withErrors(['message' => 'Too many messages from this connection. Please try again in a few minutes.']);
        }
        RateLimiter::hit($key, 600);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'nullable|email|max:150|required_without:phone',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{10,16}$/', 'required_without:email'],
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:3000',
        ], [
            'email.required_without' => 'Please give an email or a phone number so we can reply.',
            'phone.required_without' => 'Please give a phone number or an email so we can reply.',
            'phone.regex' => 'Please enter a valid mobile number.',
        ]);
        ContactMessage::create($data + ['ip' => $request->ip()]);

        return redirect()->to(route('website.contact').'#form')->with('contact_ok', $data['name']);
    }
}
