<?php

use App\Models\User;

it('soft deletes a user — record remains in database with deleted_at set', function () {
    $user = User::factory()->create();
    $userId = $user->id;

    $user->delete();

    // ยังอยู่ใน DB (withTrashed)
    $trashed = User::withTrashed()->find($userId);
    expect($trashed)->not->toBeNull();
    expect($trashed->deleted_at)->not->toBeNull();
});

it('normal query does not return soft-deleted users', function () {
    $user = User::factory()->create();
    $userId = $user->id;

    $user->delete();

    // User::find / User::all ไม่เห็น soft-deleted user
    expect(User::find($userId))->toBeNull();
    expect(User::all()->pluck('id')->contains($userId))->toBeFalse();
});

it('withTrashed returns soft-deleted users', function () {
    $user = User::factory()->create();
    $userId = $user->id;

    $user->delete();

    expect(User::withTrashed()->find($userId))->not->toBeNull();
    expect(User::withTrashed()->pluck('id')->contains($userId))->toBeTrue();
});

it('user is not hard deleted — row still exists in database', function () {
    $user = User::factory()->create();
    $userId = $user->id;

    $user->delete();

    $count = DB::table('users')->where('id', $userId)->count();
    expect($count)->toBe(1);
});

it('restore brings a soft-deleted user back to normal queries', function () {
    $user = User::factory()->create();
    $user->delete();

    $user->restore();

    expect(User::find($user->id))->not->toBeNull();
    expect(User::find($user->id)->deleted_at)->toBeNull();
});
