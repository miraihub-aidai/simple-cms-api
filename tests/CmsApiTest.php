<?php

namespace SimpleCms\Tests\Api;

use PHPUnit\Framework\TestCase;
use PDO;
use SimpleCms\Api\CmsApi;

class CmsApiTest extends TestCase
{
    private CmsApi $api;
    private PDO $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // テスト用のデータベース接続を設定
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // テスト用のテーブルを作成
        $this->db->exec("CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, content TEXT)");

        // テスト用のCmsApiインスタンスを作成
        $this->api = new CmsApi($this->db);
    }

    public function testGetAllPosts(): void
    {
        // テストデータを挿入
        $this->db->exec("INSERT INTO posts (title, content) VALUES ('Test Post 1', 'Content 1')");
        $this->db->exec("INSERT INTO posts (title, content) VALUES ('Test Post 2', 'Content 2')");

        // GETリクエストをシミュレート
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/posts';

        ob_start();
        $this->api->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertCount(2, $response);
        $this->assertEquals('Test Post 1', $response[0]['title']);
        $this->assertEquals('Test Post 2', $response[1]['title']);
    }

    public function testGetSinglePost(): void
    {
        // テストデータを挿入
        $this->db->exec("INSERT INTO posts (title, content) VALUES ('Test Post', 'Content')");

        // GETリクエストをシミュレート
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/posts/1';

        ob_start();
        $this->api->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('Test Post', $response['title']);
        $this->assertEquals('Content', $response['content']);
    }

    public function testCreatePost(): void
    {
        // POSTリクエストをシミュレート
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/posts';

        $postData = json_encode(['title' => 'New Post', 'content' => 'New Content']);
        $this->api->setRequestBody($postData);

        ob_start();
        $this->api->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('id', $response);

        // 作成された投稿を確認
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$response['id']]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('New Post', $post['title']);
        $this->assertEquals('New Content', $post['content']);
    }

    public function testUpdatePost(): void
    {
        // テストデータを挿入
        $this->db->exec("INSERT INTO posts (title, content) VALUES ('Original Post', 'Original Content')");

        // PUTリクエストをシミュレート
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/api/posts/1';

        $putData = json_encode(['title' => 'Updated Post', 'content' => 'Updated Content']);
        $this->api->setRequestBody($putData);

        ob_start();
        $this->api->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('Post updated successfully', $response['message']);

        // 更新された投稿を確認
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = 1");
        $stmt->execute();
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Updated Post', $post['title']);
        $this->assertEquals('Updated Content', $post['content']);
    }

    public function testDeletePost(): void
    {
        // テストデータを挿入
        $this->db->exec("INSERT INTO posts (title, content) VALUES ('Delete Me', 'Delete This Content')");

        // DELETEリクエストをシミュレート
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/api/posts/1';

        ob_start();
        $this->api->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('Post deleted successfully', $response['message']);

        // 投稿が削除されたことを確認
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM posts WHERE id = 1");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count);
    }

    public function testInvalidMethod(): void
    {
        // 無効なメソッドをシミュレート
        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $_SERVER['REQUEST_URI'] = '/api/posts/1';

        ob_start();
        $this->api->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Method not allowed', $response['error']);
    }

    public function testInvalidRoute(): void
    {
        // 無効なルートをシミュレート
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/invalid';

        ob_start();
        $this->api->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Not found', $response['error']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}