<x-mail::message>
# Account Activation Request

You are receiving this email to activate your account.

Your 7-digit Activation Code is:

<x-mail::panel>
**{{ $otp }}**
</x-mail::panel>

This code will expire in 15 minutes.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
