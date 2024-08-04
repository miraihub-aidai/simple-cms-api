# 前提
このプロジェクトはすべて Anthropic Claude 3.5 Sonnet で自動生成したプロジェクトです。

# CMS API プロジェクト
このプロジェクトは、PHP 8.3とSQLiteを使用したシンプルなコンテンツ管理システム（CMS）APIを実装しています。ブログ投稿の基本的なCRUD操作を提供します。

## 必要条件

- PHP 8.3以上
- Composer
- SQLite3

## セットアップ

1. リポジトリをクローンし、プロジェクトディレクトリに移動します：
   ```
   git clone https://github.com/yourusername/cms-api-project.git
   cd cms-api-project
   ```

2. Composerを使用して依存関係をインストールします：
   ```
   composer install
   ```

3. データベースディレクトリを作成します：
   ```
   mkdir database
   ```

   注意: データベースファイル（`database/cms_database.sqlite`）は初回のAPI呼び出し時に自動的に作成されます。

4. 開発用サーバーを起動します：
   ```
   php -S localhost:8000 -t public
   ```

## API エンドポイント

- `GET /api/posts`: 全ての投稿を取得
- `GET /api/posts/{id}`: 特定の投稿を取得
- `POST /api/posts`: 新しい投稿を作成
- `PUT /api/posts/{id}`: 既存の投稿を更新
- `DELETE /api/posts/{id}`: 投稿を削除

### 使用例

新しい投稿を作成：
```
curl -X POST http://localhost:8000/api/posts \
     -H "Content-Type: application/json" \
     -d '{"title":"新しい投稿","content":"これは新しい投稿の内容です。"}'
```

全ての投稿を取得：
```
curl http://localhost:8000/api/posts
```

特定の投稿を取得（IDを1と仮定）：
```
curl http://localhost:8000/api/posts/1
```

投稿を更新（IDを1と仮定）：
```
curl -X PUT http://localhost:8000/api/posts/1 \
     -H "Content-Type: application/json" \
     -d '{"title":"更新された投稿","content":"これは更新された投稿の内容です。"}'
```

投稿を削除（IDを1と仮定）：
```
curl -X DELETE http://localhost:8000/api/posts/1
```

## コードスタイル

このプロジェクトは PSR-12 コーディング規約に従っています。コードスタイルを自動的に修正するには、以下のコマンドを使用してください：

```
# src ディレクトリのみ修正
composer cs-fix

# src と tests ディレクトリを修正
composer cs-fix-all
```

## テスト

テストスイートを実行するには、以下のコマンドを使用します：

```
composer test
```

## 開発

開発中は、以下のコマンドを使用してPHP組み込みのウェブサーバーを起動できます：

```
php -S localhost:8000 -t public
```

これにより、`http://localhost:8000` でAPIにアクセスできるようになります。

## ライセンス

このプロジェクトは [MITライセンス](https://opensource.org/licenses/MIT) の下でオープンソース化されています。
