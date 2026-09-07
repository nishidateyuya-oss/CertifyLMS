<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\StoreSettingImageRequest;
use App\Http\Requests\UpdateSettingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Storage;

class SettingController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('settings.profile', compact('user'));
    }

    public function update(UpdateSettingRequest $request)
    {
        $validated = $request->validated();
        $request->user()->update($validated);

        return back()->with('success', 'プロフィールを更新しました');
    }

    public function store(StoreSettingImageRequest $request)
    {
        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatar-images', 'public');

            $user->update(['avatar_url' => $path]);
        }

        return back()->with('success', 'プロフィール画像を更新しました');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $path = $user->getRawOriginal('avatar_url');

        if ($path) {
            Storage::disk('public')->delete($path);

            $user->update(['avatar_url' => null]);
        }

        return back()->with('success', 'プロフィール画像を削除しました');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => '現在のパスワードが正しくありません']);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return redirect()->route('dashboard.index')->with('success', 'パスワードを変更しました');
    }
}
