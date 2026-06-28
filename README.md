# Backlog 風 プロジェクト管理 SaaS

プロジェクト単位のタスク管理 SaaS。個人（フリーランス）も法人組織も同じ仕組みで使え、
プロジェクトは組織をまたいだコラボレーションが可能、利用量に応じたフリーミアム課金を行う。

設計の全文は `~/.claude/plans/backlog-zany-treasure.md` を参照。

## 構成

- **backend/** — Laravel 11 (PHP 8.3) の JSON API。クリーンアーキテクチャ
  （Controller → UseCase → Service → Repository → Resource）。認証は Sanctum、課金は Cashier(Stripe)。
- **frontend/** — Next.js 15 (App Router, TypeScript)。API を消費。
- **docker/** — php / nginx の Docker 設定。
- 型は Laravel の DTO（spatie/laravel-data）から `typescript:transform` で
  `frontend/src/types/generated.d.ts` に自動生成。

## サービス（docker compose）

| サービス | URL |
|----------|-----|
| アプリ（nginx 経由） | http://localhost:8080 |
| Next.js（直接） | http://localhost:3000 |
| Mailhog | http://localhost:8025 |
| MinIO Console | http://localhost:9001 |
| MySQL | localhost:3307 |

## よく使うコマンド

```bash
make up          # スタック起動
make art c="migrate"   # artisan 実行
make test        # バックエンドテスト（カバレッジ80%閾値）
make types       # TS 型を再生成
make fresh       # DB を作り直して seed
```
