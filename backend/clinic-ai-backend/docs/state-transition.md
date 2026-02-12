# 状態遷移詳細設計書

**バージョン**: 1.0.0  
**最終更新**: 2026-02-12  
**憲法準拠**: 第10条（状態遷移）

---

## 1. 状態定義

### 1.1 全状態一覧

| 状態 | 値 | 名称 | 説明 |
|-----|---|------|------|
| S0 | `S0` | 未受付 | 患者が来院前または受付前 |
| S1 | `S1` | 受付済 | 受付完了、待機列への追加準備中 |
| S2 | `S2` | 待機中 | 待合室で順番待ち |
| S3 | `S3` | 呼出中 | 診察室から呼び出し済み |
| S4 | `S4` | 診察中 | 診察進行中 |
| S5 | `S5` | 再呼出 | 呼出に応答なし、再度呼び出し待ち |
| S6 | `S6` | 診察終了 | 診察完了、会計待ち |
| S7 | `S7` | 会計待 | 会計受付待ち |
| S8 | `S8` | 会計中 | 会計処理中 |
| S9 | `S9` | 完了 | 全フロー完了（終端状態） |

---

## 2. 状態遷移図

### 2.1 完全遷移図

```
        ┌──────┐
   ───► │  S0  │ 未受付
        └───┬──┘
            │ 受付
            ▼
        ┌──────┐
        │  S1  │ 受付済
        └───┬──┘
            │ 待機列追加
            ▼
        ┌──────┐
        │  S2  │ 待機中
        └──┬┬──┘
           ││
           │└──────────────┐ 診察なし会計（例外）
           │               │
           │呼出           ▼
           ▼           ┌──────┐
        ┌──────┐       │  S7  │ 会計待
   ┌──► │  S3  │ 呼出中└───┬──┘
   │    └──┬┬──┘           │ 会計開始
   │       ││               ▼
   │再呼出 ││入室       ┌──────┐
   │       ││           │  S8  │ 会計中
   │       │▼           └───┬──┘
   │       │┌──────┐        │ 会計完了
   │       ││  S4  │ 診察中 │
   │       │└───┬──┘        │
   │       │    │ 診察終了  │
   │       │    ▼           │
   │       │┌──────┐        │
   │       ││  S6  │ 診察終了│
   │       │└───┬──┘        │
   │       │    │           │
   │       │    └───────────┘
   │       │                ▼
   │       │            ┌──────┐
   │       │            │  S9  │ 完了
   │       │            └──────┘
   │       │
   │       ▼
   │   ┌──────┐
   └───│  S5  │ 再呼出
       └──────┘
```

---

## 3. 遷移ルール

### 3.1 正常フロー

| From | To | 条件 | トリガー |
|------|----|----|---------|
| S0 | S1 | なし | 受付処理 |
| S1 | S2 | なし | 自動（受付完了後） |
| S2 | S3 | 待機中 | 診察室から呼出 |
| S3 | S4 | 呼出中 | 患者入室 |
| S4 | S6 | 診察中 | 診察終了操作 |
| S6 | S7 | 診察終了 | 自動 |
| S7 | S8 | 会計待 | 会計開始操作 |
| S8 | S9 | 会計中 | 会計完了操作 |

### 3.2 例外フロー

#### 3.2.1 再呼出（S3 → S5 → S3）

**条件**:
- 現在の状態が S3（呼出中）
- 患者が一定時間応答しない

**制約**:
- 最大3回まで再呼出可能
- `recall_count` フィールドで回数管理

**実装**:
```php
// 再呼出可能かチェック
if ($visit->current_state === VisitState::S3 && $visit->recall_count < 3) {
    $visitStateService->transition($visit, VisitState::S5->value);
    // recall_count は自動インクリメント
}
```

#### 3.2.2 診察なし会計（S2 → S7）

**条件**:
- 現在の状態が S2（待機中）
- 診察不要な処理（処方箋のみ、検査結果説明のみ等）

**制約**:
- 遷移理由の記録が必須
- `is_no_exam` フラグが true に設定される

**実装**:
```php
$visitStateService->transition(
    $visit, 
    VisitState::S7->value, 
    '処方箋のみ' // 理由必須
);
```

---

## 4. 禁止遷移

### 4.1 不正遷移の例

| From | To | 理由 |
|------|----|----|
| S0 | S9 | 受付せずに完了は不可 |
| S2 | S4 | 呼出なしで診察開始は不可 |
| S4 | S2 | 診察中から待機中への後退は不可 |
| S9 | * | 完了後の状態変更は不可（終端状態） |

### 4.2 エラーハンドリング

```php
// VisitStateService::transition() 内
if (! VisitStateTransition::can($fromState, $toState)) {
    throw new StateTransitionException(
        "Invalid visit state transition: {$fromState} → {$toState}"
    );
}
```

---

## 5. タイムスタンプ記録

### 5.1 状態ごとのタイムスタンプ

| 状態 | カラム名 | 記録タイミング |
|-----|---------|--------------|
| S1 | `accepted_at` | 受付完了時 |
| S3 | `called_at` | 呼出時 |
| S4 | `exam_started_at` | 診察開始時 |
| S6 | `exam_ended_at` | 診察終了時 |
| S8 | `paid_at` | 会計完了時 |
| S9 | `ended_at` | 全フロー完了時 |

### 5.2 実装

```php
// VisitStateService::timestampsFor()
private function timestampsFor(string $state): array
{
    return match ($state) {
        VisitState::S1->value => ['accepted_at' => now()],
        VisitState::S3->value => ['called_at' => now()],
        VisitState::S4->value => ['exam_started_at' => now()],
        VisitState::S6->value => ['exam_ended_at' => now()],
        VisitState::S8->value => ['paid_at' => now()],
        VisitState::S9->value => ['ended_at' => now()],
        default               => [],
    };
}
```

---

## 6. ログ記録

### 6.1 StateLog テーブル

全ての状態遷移は `state_logs` テーブルに記録される。

**記録項目**:
- `visit_id`: 対象Visit
- `from_state`: 遷移前状態
- `to_state`: 遷移後状態
- `reason`: 遷移理由（例外フローで必須）
- `changed_at`: 遷移日時

### 6.2 監査要件

- 全ログは削除不可（憲法第14条）
- 閲覧専用（更新不可）
- KPI算出の根拠データ

---

## 7. テストケース

### 7.1 正常系

```php
// S0 → S1 → S2 → S3 → S4 → S6 → S7 → S8 → S9
$visit = Visit::factory()->create(['current_state' => 'S0']);

$service->transition($visit, 'S1');
$this->assertEquals('S1', $visit->fresh()->current_state);
$this->assertNotNull($visit->fresh()->accepted_at);

$service->transition($visit, 'S2');
$this->assertEquals('S2', $visit->fresh()->current_state);
// ... 以下同様
```

### 7.2 例外フロー

```php
// 再呼出: S3 → S5 → S3
$visit = Visit::factory()->create(['current_state' => 'S3', 'recall_count' => 0]);

$service->transition($visit, 'S5');
$this->assertEquals('S5', $visit->fresh()->current_state);
$this->assertEquals(1, $visit->fresh()->recall_count);

$service->transition($visit, 'S3');
$this->assertEquals('S3', $visit->fresh()->current_state);
```

```php
// 診察なし会計: S2 → S7
$visit = Visit::factory()->create(['current_state' => 'S2']);

$service->transition($visit, 'S7', '処方箋のみ');
$this->assertEquals('S7', $visit->fresh()->current_state);
$this->assertTrue($visit->fresh()->is_no_exam);
```

### 7.3 異常系

```php
// 不正遷移: S0 → S9
$visit = Visit::factory()->create(['current_state' => 'S0']);

$this->expectException(StateTransitionException::class);
$service->transition($visit, 'S9');
```

---

## 8. パフォーマンス考慮

### 8.1 トランザクション

全ての状態遷移は DB トランザクション内で実行される。

```php
DB::transaction(function () use ($visit, $fromState, $toState, $reason) {
    $visit->update([...]);
    StateLog::create([...]);
});
```

### 8.2 ロック戦略

- 悲観的ロックは不使用（1患者=1セッション想定）
- 楽観的ロックも不要（状態遷移は順次実行）

---

**この状態遷移設計は憲法第10条に完全準拠し、全フローの安全性を保証します。**