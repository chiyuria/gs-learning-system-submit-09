# README（更新版）

## ① 課題名

**Learning System Prototype（AIフィードバック連携）**

---

## ② 課題内容（どんな作品か）

ログイン後、ユーザー（学生）が **3つの設問に回答**し、
その回答内容をもとに **AIによるフィードバックを受け取り**、
最終的に **ワーク（記述課題）を作成・保存**できる
**学習支援用のWebアプリ・プロトタイプ**です。

PHP + MySQL による基本的なCRUD処理に加え、

* ログイン認証（セッション管理）
* ロール別アクセス制御（学生／教員／管理者）
* 入力状況に応じた画面遷移・表示制御
* 外部AI API（Gemini）とのサーバーサイド連携

を含んだ、**一連のフローを意識した構成**を実装しています。

---

## ③ アプリのデプロイURL

**アプリURL**
[https://www.logic-craft.jp/learning-system/index.php](https://www.logic-craft.jp/learning-system/index.php)

---

## ④ ログイン情報（テスト用）

本アプリはログイン認証ありの構成です。

### テスト用アカウント

* **学生**

  * ID：`stu001`
  * PW：`stu001`

* **教員**

  * ID：`tch001`
  * PW：`tch001`

* **管理者**

  * ID：`admin`
  * PW：`admin`

※ AIフィードバックの実行は
**1ユーザー／1テーマにつき1回まで**に制限しています。

---

## ⑤ ディレクトリ構成

```
LEARNING-SYSTEM
├─ config/
│  └─ ls/
│     ├─ db.php        # DB接続設定
│     └─ ai.php        # AI（Gemini）設定
│
├─ inc/
│  └─ ls/
│     ├─ functions.php # 共通関数（DB / escape / redirect）
│     ├─ auth.php      # 認証・ロール制御
│     ├─ AiClient.php  # AI API クライアント
│     └─ PromptBuilder.php # AIプロンプト生成
│
├─ SQL/
│  └─ learn_sys.sql    # テーブル定義
│
├─ tools/
│  └─ make_hash.php    # パスワード生成用
│
├─ www/learning-system/
│  ├─ assets/css/      # CSS（Tokens / Base / Components / Pages）
│  │
│  ├─ index.php        # エントリーポイント
│  ├─ login.php
│  ├─ login_action.php
│  ├─ logout_action.php
│  │
│  ├─ question.php         # 設問入力（表示）
│  ├─ question_action.php  # 設問保存 + AI実行
│  │
│  ├─ work.php             # ワーク表示
│  ├─ work_action.php      # ワーク保存
│  │
│  ├─ admin_dashboard.php  # 管理画面
│  └─ admin_users.php      # ユーザー管理
│
├─ .gitignore
└─ README.md
```

---

## ⑥ こだわった点

### ■ 処理の責務分離（表示 / 処理）

画面表示とPOST処理が混在しないよう、
各機能で **表示用ファイル** と **action用ファイル** を分離しています。

```
question.php        → 表示
question_action.php → 保存・AI実行
```

---

### ■ 共通処理・設定の分離

* DB接続：`config/ls/db.php`
* AI設定：`config/ls/ai.php`
* 共通処理：`inc/ls/`

設定と処理を分離することで、
**デプロイ環境や機能拡張時の影響範囲を限定**しています。

---

### ■ AI連携の安全設計（プロトタイプ前提）

AI実行は以下の制御を入れています。

* サーバーサイドからのみAPI実行
* 実行履歴をDBで管理（ai_requests / ai_outputs）
* 同一ユーザー・同一テーマでの再実行防止

APIキーをGitHubに含めずに公開できる構成を意識しました。

---

### ■ CSSのレイヤー分割

CSSは役割ごとに分割しています。

* Tokens：色・余白・フォント定義
* Base：reset / typography / layout
* Components：ボタン・フォーム・テーブル等
* Pages：ページ固有スタイル
* Utilities：補助クラス

---

## ⑦ 難しかった点・今後の課題

### ■ 難しかった点

* セッション管理とロール制御
* 設問回答の状態管理
* AI連携処理の責務分離
* ディレクトリ構成の整理
* 再実行制御を含めた安全設計

---

### ■ 今後の課題

* バリデーション強化
* CSRF対策
* 管理画面の拡張（検索・集計）
* テーマ・設問の動的切り替え
* 教員向けの学習状況確認機能

---

## ⑧ 補足

本プロトタイプは、
「動くこと」だけでなく **構成・拡張を前提とした設計**を意識して実装しています。

---