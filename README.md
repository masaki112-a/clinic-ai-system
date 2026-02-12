# clinic-ai-system

AIを活用したクリニック受付・診察支援システム

---

## 📋 プロジェクト概要

患者の受付から会計までの業務フローを、AI技術とモダンなWeb技術で効率化するシステムです。

### 主要機能

- ✅ **受付管理**: QRコード/手動受付
- ✅ **呼出システム**: 待合室での患者呼出・不在管理
- ✅ **診察管理**: 診察室入退室管理
- 🔄 **会計管理**: 診察終了から会計までの流れ（Phase 3実装中）
- 📊 **ダッシュボード**: リアルタイム状況表示（Phase 4予定）

---

## 🏗️ アーキテクチャ

### 設計思想

- **APIファースト**: RESTful API設計
- **リソース中心**: Visitリソースを中心とした状態管理
- **状態遷移の明示性**: 各状態遷移が専用エンドポイントを持つ

詳細は [憲法文書](docs/CONSTITUTION.md) を参照してください。

### 技術スタック

**バックエンド:**
- Laravel 11.xS
- PHP 8.3
- MySQL 8.0

**フロントエンド（Phase 4予定）:**
- Next.js 14
- React 18
- TypeScript

---

## 📊 開発状況

### 完了したPhase

**Phase 0: 基盤構築** ✅ (30 tests)
- 状態マシン（S0-S9）
- 状態遷移サービス
- データベース設計

**Phase 1: 受付機能** ✅ (31 tests)
- QR受付API
- 手動受付API
- 一覧・詳細取得API

**Phase 2: 呼出機能** ✅ (37 tests)
- 呼出API
- 診察室入室API
- 不在マークAPI
- 再呼出API

**合計: 98 tests passed**

---

## 🚀 セットアップ

### 前提条件

- PHP 8.3以上
- Composer
- MySQL 8.0以上

### インストール

```bash
cd backend/clinic-ai-backend

# 依存関係インストール
composer install

# 環境設定
cp .env.example .env
php artisan key:generate

# データベースセットアップ
php artisan migrate

# テスト実行
php artisan test