# CLAUDE.md (backend)

Laravel 11 / PHP 8.3 の JSON API。クリーンアーキテクチャ（レイヤード）で実装する。
リポジトリ全体の概要・コマンド・マルチテナント/クォータの仕組みはルートの `../CLAUDE.md` を参照。
ここではバックエンドの**レイヤー責務**と**コーディング/フォーマット規約**を定める。

## アーキテクチャ（依存は上から下への一方向）

```
HTTP Request
  → Controller        app/Http/Controllers/Api/
  → FormRequest       app/Http/Requests/          （バリデーション）
  → UseCase           app/UseCases/               （トランザクション境界・1操作1ユースケース）
  → Service           app/Services/               （横断的ドメインロジック）
  → Repository         app/Repositories/           （Contracts インターフェースに依存）
  → Model (Eloquent)  app/Models/
  → Data (Resource)   app/Data/                   （レスポンス整形 + TS 型の生成元）
```

各層の責務:

- **Controller** — 薄く保つ。`$this->authorize(...)` でポリシー認可 → UseCase に委譲 → `Data`/`DataCollection` を返すだけ。ビジネスロジック・DB アクセス・条件分岐を書かない。UseCase とリポジトリ Interface はメソッド引数で型ヒント注入する。
- **FormRequest** — バリデーションは必ずここ。`rules()` に加えて `payload()` を実装し、`validated()` を enum / 値オブジェクトにキャストした配列を返す。UseCase には**この `payload()` の配列**を渡し、生の Request は渡さない。
- **UseCase** — アプリケーションの 1 操作 = 1 クラス。`AbstractUseCase` を継承し、永続化は必ず `$this->transaction(fn () => ...)` で囲む（**トランザクション境界はここだけ**）。メール送信・Stripe・S3 などの外部副作用はコミット後に遅延させる（`DB::afterCommit()` またはキュー）。`handle(...)` を公開メソッドにする。
- **Service** — 複数ユースケースで再利用する横断的ドメインロジック（例 `QuotaService`、`OrganizationProvisioner`、`DefaultProjectStatuses`）。クォータ超過などは専用例外（`QuotaExceededException`）を投げる。
- **Repository** — 永続化の抽象化。UseCase / Service は `App\Repositories\Contracts\*Interface` にのみ依存し、Eloquent 実装に直接依存しない。実装は `app/Repositories/Eloquent/`。新しい Interface→実装の束縛は必ず `app/Providers/RepositoryServiceProvider.php` の `$bindings` に登録する（テストでフェイクに差し替えられるようにするため）。
- **Data**（spatie/laravel-data） — API レスポンスであると同時に、`make types` で生成される TypeScript 型（`frontend/src/types/generated.d.ts`）の生成元。Data クラスを変更したら `make types` を実行する。`fromModel()` でモデルから組み立て、コントローラで `->toResponse($request)` する。

### 新しいエンドポイントを追加する手順

1. `routes/api.php` にルート追加（テナントスコープが必要なら `Route::middleware('organization')` グループ内へ）
2. `app/Policies/` に認可を追加（必要なら）
3. FormRequest（`rules()` + `payload()`）
4. UseCase（`AbstractUseCase` 継承、`transaction()` で囲む）
5. 必要なら Repository Interface にメソッド追加 → Eloquent 実装 → `RepositoryServiceProvider` 確認
6. Data クラス → `make types`
7. Pest テスト（`make test`、カバレッジ 80% 閾値）

## フォーマット / コーディング規約

- **整形は Laravel Pint に準拠**（`laravel/pint`、デフォルト = Laravel プリセット、PSR-12 ベース）。`pint.json` は置かずデフォルト設定を使う。
  実行: `docker compose run --rm --no-deps php ./vendor/bin/pint`（差分確認は `--test`）。
- `.editorconfig` に従う: PHP はスペース 4 インデント、LF、末尾改行あり、行末空白除去。
- すべてのファイル先頭に `declare(strict_types=1)` は付けていない（既存コードに合わせる。新規も既存踏襲）。型は引数・戻り値・プロパティに必ず付け、配列は PHPDoc（`@param array<string, mixed>`）で要素型を補う。
- `readonly` プロパティでのコンストラクタ注入を使う（既存の UseCase / Service / Middleware を踏襲）。
- enum はファーストクラスの PHP enum（`app/Enums/`）。FormRequest の `payload()` で文字列から enum に変換してから UseCase へ渡す。

## Git / ブランチ運用

- ブランチ戦略: `main`（本番）/ `develop`（統合）/ `feature/*`。`feature/*` は `develop` から切り、PR で `develop` へマージ。リリース時に `develop` → `main`。
- `main` / `develop` へ直接コミットしない（必ず `feature/*` で作業）。
- **コミット前に `make test`（Pint + Pest, カバレッジ `--min=80`）が通ることを確認する**。

## テスト

- Pest 3 + PHPUnit 11。`make test` でカバレッジ `--min=80`（閾値は CI 相当で常時担保）。
- 単体実行: `docker compose run --rm php php artisan test --filter=CreateTaskTest`。
- Factory は `database/factories/`。リポジトリはフェイク差し替え可能な設計なので、UseCase の単体テストでは Interface をモックしてよい。

## 注意点

- spatie/laravel-data の `toResponse()` は **POST で既定 201**。200 にしたい場合は `->setStatusCode(200)`（`update` 系コントローラ参照）。
- Sanctum SPA Cookie 認証は `Origin` ヘッダが無いと stateful 判定されず 401。テスト/curl では注意。
- composer はプロジェクトの PHP 8.3 イメージ（`docker/php`）で実行する（公式 composer イメージは PHP 8.5 で `moneyphp/money` が壊れる）。
