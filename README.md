アプリケーション名　：　attendance-app

●環境構築
　・git clone : リンク先URLを入れる！！
   cd attendance-app
　・docker-compose up -d --build

●Laravel環境構築
　・docker-compose exec php bash
　・composer install
　・cp .env.example .env、環境変数を変更
　・php artisan key:generate
　・php artisan migrate
　・php artisan db:seed

●メール認証について
　・mailhogを使用しています
　・http://localhost:8025でアクセス
　・.env設定
　　MAIL_MAILER=smtp
   MAIL_HOST=mailhog
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   MAIL_FROM_ADDRESS=noreply@example.com
   MAIL_FROM_NAME="${APP_NAME}"

●ユーザー登録、ログインURL
　管理者ログイン：http://localhost/admin/login
　ユーザー登録：http://localhost/register
　一般ユーザーログイン：http://localhost/login

●サンプルアカウント
　管理者
　name：テスト管理者
　email：admin1@example.com
　password：password

　一般ユーザー
　name：テストユーザー3
　email：test3@examle.com
　password：password
　※一般のテストユーザーはテストユーザー3〜テストユーザー8まで登録しています。
　 パスワードは全ユーザー共通です。
  名前、emailの数字を3〜8に変更してログインして下さい。

●テスト環境構築
　・docker-compose exec mysql bash
　・mysql -u root -p（パスワードは「root」）
　・create database test_database;

　・docker-compose exec php bash
　・php artisan migrate:fresh --env=testing
　・./vendor/bin/phpunit

●その他特記事項
　・機能要件のFN039の1のバリデーションメッセージが11の1つ目のテスト項目のバリデーションメッセージと違った為、機能要件のバリデーションメッセージで単体テストを行いました。
　・Laravel 8.83.29
　　PHP 8.1.33
