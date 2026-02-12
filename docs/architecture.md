# クリニックAI統合システム - アーキテクチャ設計書

**バージョン**: 1.0.0  
**最終更新**: 2026-02-12  
**憲法準拠**: 全条項

---

## 1. システム概要

### 1.1 目的
クリニックの受付〜診察〜会計の全フローをデジタル化し、AIによる文字起こし・要約機能を統合する。

### 1.2 技術スタック（憲法第2条：固定）

| レイヤー | 技術 | バージョン |
|---------|------|----------|
| Backend | Laravel | 11.x |
| Frontend | Blade + Tailwind CSS | 4.x |
| Database | PostgreSQL | 16.x |
| AI (文字起こし) | OpenAI Whisper | API v1 |
| AI (要約) | OpenAI GPT-4 | API v1 |
| 音声入力 | Web Audio API | - |

---

## 2. レイヤーアーキテクチャ

```
┌─────────────────────────────────────────────────────────┐
│                    Presentation Layer                    │
│  (Blade Views + JavaScript + Tailwind CSS)              │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                   Controller Layer                       │
│  ReceptionController │ ExamController │ AdminController │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                    Service Layer                         │
│  VisitStateService │ UiLockService │ AiConfigService    │
│  (ビジネスロジックの中枢)                                │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                     Model Layer                          │
│  Visit │ ExamSession │ StateLog │ UiLock │ AiPrompt    │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                  Database (PostgreSQL)                   │
└─────────────────────────────────────────────────────────┘
```

---

## 3. 主要コンポーネント

### 3.1 状態管理システム

**責務**: 患者の来院フロー全体を状態遷移で管理

**主要クラス**:
- `App\Enums\VisitState` - 状態定義（S0〜S9）
- `App\Services\VisitStateService` - 状態遷移実行
- `App\Services\VisitStateTransition` - 遷移ルール検証

**状態一覧**:
```
S0: 未受付 → S1: 受付済 → S2: 待機中 → S3: 呼出中 → S4: 診察中 
→ S6: 診察終了 → S7: 会計待 → S8: 会計中 → S9: 完了

例外フロー:
- S2 → S7（診察なし会計）
- S3 → S5 → S3（再呼出）
```

---

### 3.2 排他制御システム

**責務**: 複数端末での同時操作を防止

**主要クラス**:
- `App\Services\UiLockService` - ロック取得/解放
- `App\Models\UiLock` - ロック状態保存

**ロック対象UI**:
- 受付画面（reception）
- 診察室画面（exam）
- 管理画面（admin）

**ロック解除条件**:
- 正常終了（明示的な解除）
- タイムアウト（デフォルト10分）
- 強制解除（管理画面から、理由必須）

---

### 3.3 診察セッション管理

**責務**: 診察中のAI設定・文字起こし・要約を管理

**主要クラス**:
- `App\Models\ExamSession` - 診察セッション
- `App\Services\ExamSessionStateService` - セッション状態管理
- `App\Services\AiConfigService` - AI設定スナップショット（Phase 4）

**設計原則**:
- 診察開始時にAI設定を確定（憲法第12条）
- 診察中はAI設定変更不可
- セッション単位でログ・データを分離

---

### 3.4 AI統合システム（Phase 4以降）

**責務**: プロンプト・辞書管理、文字起こし、要約

**主要クラス**:
- `App\Models\AiPrompt` - プロンプト管理
- `App\Models\AiDictionary` - 医療用語辞書
- `App\Services\TranscriptionService` - Whisper API統合
- `App\Services\SummaryService` - GPT-4 API統合

**辞書優先順位**（憲法第13条）:
```
system（システム標準） > clinic（クリニック独自） > user（ユーザー個別）
```

---

## 4. データフロー

### 4.1 受付フロー

```
患者QR/IC読み取り
    ↓
ReceptionController::accept()
    ↓
VisitStateService::transition(S0 → S1)
    ↓
StateLog記録
    ↓
VisitStateService::transition(S1 → S2)
    ↓
待合室画面に表示
```

### 4.2 診察フロー

```
診察室画面で「次の患者を呼ぶ」
    ↓
WaitingRoomService::callNext()
    ↓
VisitStateService::transition(S2 → S3)
    ↓
患者が入室
    ↓
ExamController::start()
    ↓
VisitStateService::transition(S3 → S4)
    ↓
ExamSessionStateService::startExam()
    ↓
AI設定スナップショット作成（Phase 4）
    ↓
文字起こし開始（Phase 5）
    ↓
診察終了
    ↓
ExamController::end()
    ↓
VisitStateService::transition(S4 → S6)
    ↓
要約生成（Phase 6）
```

---

## 5. セキュリティ設計

### 5.1 データ保護

**保存するデータ**:
- 文字起こしテキスト
- 要約テキスト
- 状態遷移ログ
- AI設定変更ログ

**保存しないデータ**（憲法第16条）:
- 音声データ（録音禁止）
- 個人情報原本（氏名・住所等）

### 5.2 アクセス制御

- 全UI画面は認証不要（クリニック内ネットワーク限定運用想定）
- UIロックによる排他制御
- 管理画面は強制解除時に操作者ID・理由を記録

---

## 6. 拡張性設計

### 6.1 将来実装予定（憲法第17条）

**設計済み・未実装**:
- AI学習ログ（要約品質評価）
- 辞書ロールバック機能
- 診療科別AI設定

**未決定**:
- AI自動改善機能
- 他システム連携（電子カルテ等）

### 6.2 拡張ポイント

```php
// Eventシステム（将来実装）
event(new VisitStateChanged($visit, $fromState, $toState));

// Queueシステム（Phase 6で実装検討）
SummaryJob::dispatch($examSession);
```

---

## 7. 運用設計

### 7.1 多重起動防止（憲法第4条）

- 各UI画面は1端末のみ起動可能（UiLockで制御）
- ホーム画面からのみ起動可能
- ロック中は他端末から起動不可

### 7.2 エラーリカバリ

**タイムアウト処理**:
- UIロック: 10分で自動解除
- 診察セッション: タイムアウトなし（手動終了必須）

**強制解除**:
- 管理画面から強制解除可能
- 理由の記録必須
- UiLockLogに証跡保存

---

## 8. テスト戦略

### 8.1 単体テスト

- 全Serviceクラスのメソッド
- Enumの遷移ルール
- Model のリレーション・メソッド

### 8.2 統合テスト

- 状態遷移フロー（正常系・異常系）
- UIロック取得・解放
- 診察フロー全体

### 8.3 E2Eテスト（Phase 7以降）

- 受付〜会計完了までの完全フロー
- 複数患者の並行処理

---

## 9. パフォーマンス設計

### 9.1 想定負荷

- 同時診察数: 1〜3
- 1日あたり患者数: 30〜100人
- ピーク時: 午前中（10〜20人/時）

### 9.2 最適化方針

- ページネーション不要（待機患者は常に全件表示）
- キャッシュ不要（リアルタイム性優先）
- N+1問題回避（Eager Loading使用）

---

## 10. 監視・ログ設計

### 10.1 記録するログ

| ログ種別 | テーブル | 保存期間 |
|---------|---------|---------|
| 状態遷移 | state_logs | 永続 |
| UIロック操作 | ui_lock_logs | 永続 |
| AI設定変更 | ai_setting_change_logs | 永続 |
| アプリケーションログ | storage/logs/laravel.log | 30日 |

### 10.2 KPI（Phase 7で実装）

- 平均待機時間
- 平均診察時間
- 再呼出率
- 診察なし会計率

---

## 11. デプロイ戦略

### 11.1 環境構成

- **開発環境**: ローカル（Docker想定）
- **本番環境**: クリニック内サーバー（GPU搭載想定）

### 11.2 マイグレーション戦略

- `php artisan migrate` 実行
- 本番環境では事前バックアップ必須
- ロールバック可能な設計

---

**このアーキテクチャは憲法準拠を前提とし、安全性・保守性・拡張性を最優先に設計されています。**