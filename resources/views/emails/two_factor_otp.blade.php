<x-mail::message>
# Two-Factor Authentication (2FA)

@if($type === 'enable')
You are receiving this email because you requested to enable Two-Factor Authentication (2FA) for your account.

Use the following 6-digit verification code to complete your 2FA setup:
@else
You are receiving this email because a sign-in attempt requires Two-Factor Authentication (2FA).

Your 6-digit One-Time Password (OTP) is:
@endif

<x-mail::panel>
**{{ $otp }}**
</x-mail::panel>

This code is valid for 10 minutes. 

If you did not request this code, please secure your account immediately or contact support.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
