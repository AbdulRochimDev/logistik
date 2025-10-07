<?php

it('exposes authentication defaults via config bridge', function (): void {
    expect(config('wms.auth.admin_password'))->toBeString();
    expect(config('wms.auth.driver_password'))->toBeString();
});

it('exposes database SSL settings via config bridge', function (): void {
    expect(config('wms.database.ssl.enabled'))->not()->toBeNull();
});
