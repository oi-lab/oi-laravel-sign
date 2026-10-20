<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use OiLab\OiLaravelSign\Models\SignatureOtp;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\Notifications\OtpNotification;
use OiLab\OiLaravelSign\OiLaravelSign;
use OiLab\OiLaravelSign\Otp\MailOtpChannel;
use OiLab\OiLaravelSign\Otp\SmsOtpChannel;

it('masks the destination of the code', function () {
    $request = new SignatureRequest(['signer_email' => 'jeanne@example.com', 'signer_phone' => '+33 6 12 34 56 78']);

    expect((new MailOtpChannel)->destination($request))->toBe('j***@example.com')
        ->and((new SmsOtpChannel)->destination($request))->toBe('*********78');
});

it('resolves the configured channels', function () {
    expect(OiLaravelSign::otpChannel(OtpChannelType::Mail))->toBeInstanceOf(MailOtpChannel::class)
        ->and(OiLaravelSign::otpChannel(OtpChannelType::Sms))->toBeInstanceOf(SmsOtpChannel::class);
});

it('sends the SMS through the configured notification channel', function () {
    config()->set('oi-laravel-sign.otp.sms.via', 'custom-sms');
    $request = new SignatureRequest(['signer_name' => 'Jeanne', 'signer_email' => 'j@example.com', 'signer_phone' => '0612345678', 'props' => ['branding' => ['name' => 'Acme']]]);

    (new SmsOtpChannel)->send($request, '123456');

    Notification::assertSentOnDemand(
        OtpNotification::class,
        fn (OtpNotification $notification, array $channels) => $channels === ['custom-sms']
            && str_contains($notification->toSms(new AnonymousNotifiable), '123456'),
    );
});

it('keeps only an HMAC of the code', function () {
    $otp = new SignatureOtp(['code_hash' => SignatureOtp::hashCode('042042')]);

    expect($otp->code_hash)->not->toContain('042042')
        ->and($otp->matches('042042'))->toBeTrue()
        ->and($otp->matches('042043'))->toBeFalse();
});
