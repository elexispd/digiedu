<x-mail::message>
# Password Reset Request

You are receiving this email because we received a password reset request for your account.

Your 6-digit One-Time Password (OTP) is:

<x-mail::panel>
**{{ $otp }}**
</x-mail::panel>

This OTP will expire in 15 minutes. 
If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
