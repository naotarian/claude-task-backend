@component('mail::message')
# 組織への招待

**{{ $organizationName }}** に招待されました。

下のボタンから参加できます（ログイン後、招待先メールアドレスと一致している必要があります）。

@component('mail::button', ['url' => $acceptUrl])
招待を受ける
@endcomponent

このリンクは7日間有効です。心当たりがない場合は破棄してください。

ありがとうございます。<br>
{{ config('app.name') }}
@endcomponent
