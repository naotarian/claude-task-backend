<?php

use App\Models\Organization;
use App\Support\CurrentOrganization;

it('現在の組織が未解決のとき例外を投げる', function () {
    $current = new CurrentOrganization;

    expect($current->has())->toBeFalse();
    $current->get();
})->throws(RuntimeException::class);

it('現在の組織を保持して返す', function () {
    $current = new CurrentOrganization;
    $org = new Organization(['name' => 'Acme']);
    $org->id = 42;

    $current->set($org);

    expect($current->has())->toBeTrue();
    expect($current->get())->toBe($org);
    expect($current->id())->toBe(42);
});
