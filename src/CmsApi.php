<?php

declare(strict_types=1);

namespace SimpleCms\Api;

use PDO;
use PDOException;

class CmsApi
{
    protected PDO $db;
    protected ?string $requestBody = null;

    public function __construct(?PDO $db = null)
    {
        if ($db === null) {
            $this->connectDatabase();
        } else {
            $this->db = $db;
        }
    }

    protected function connectDatabase(): void
    {
        try {
            $projectRoot = $this->getProjectRoot();
            $dbDir = $projectRoot . '/database';
            $dbPath = $dbDir . '/cms_database.sqlite';

            // データベースディレクトリが存在しない場合は作成
            if (! is_dir($dbDir)) {
                if (! mkdir($dbDir, 0755, true)) {
                    throw new PDOException("Failed to create database directory");
                }
            }

            // データベースファイルが存在しない場合は作成
            if (! file_exists($dbPath)) {
                $this->db = new PDO('sqlite:' . $dbPath);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->createTableIfNotExists();
                chmod($dbPath, 0666); // ファイルのパーミッションを設定
            } else {
                $this->db = new PDO('sqlite:' . $dbPath);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    protected function getProjectRoot(): string
    {
        $currentDir = __DIR__;
        while (! file_exists($currentDir . '/composer.json')) {
            $parentDir = dirname($currentDir);
            if ($parentDir === $currentDir) {
                throw new \RuntimeException("Unable to find project root");
            }
            $currentDir = $parentDir;
        }

        return $currentDir;
    }

    private function createTableIfNotExists(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT NOT NULL
            )
        ");
    }

    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        if (preg_match('/^\/api\/posts(\/.+)?/', $path, $matches)) {
            $id = isset($matches[1]) ? substr($matches[1], 1) : null;
            switch ($method) {
                case 'GET':
                    $this->getPosts($id);

                    break;
                case 'POST':
                    $this->createPost();

                    break;
                case 'PUT':
                    $this->updatePost($id);

                    break;
                case 'DELETE':
                    $this->deletePost($id);

                    break;
                default:
                    $this->sendResponse(405, ['error' => 'Method not allowed']);
            }
        } else {
            $this->sendResponse(404, ['error' => 'Not found']);
        }
    }

    private function getPosts(?string $id): void
    {
        if ($id !== null) {
            $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->sendResponse(200, $post ?: ['error' => 'Post not found']);
        } else {
            $stmt = $this->db->query("SELECT * FROM posts");
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->sendResponse(200, $posts);
        }
    }

    private function createPost(): void
    {
        $data = json_decode($this->getRequestBody(), true);
        if (! isset($data['title']) || ! isset($data['content'])) {
            $this->sendResponse(400, ['error' => 'Invalid input']);

            return;
        }
        $stmt = $this->db->prepare("INSERT INTO posts (title, content) VALUES (?, ?)");
        $stmt->execute([$data['title'], $data['content']]);
        $this->sendResponse(201, ['id' => $this->db->lastInsertId()]);
    }

    private function updatePost(?string $id): void
    {
        if ($id === null) {
            $this->sendResponse(400, ['error' => 'Invalid ID']);

            return;
        }
        $data = json_decode($this->getRequestBody(), true);
        if (! isset($data['title']) || ! isset($data['content'])) {
            $this->sendResponse(400, ['error' => 'Invalid input']);

            return;
        }
        $stmt = $this->db->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$data['title'], $data['content'], $id]);
        $this->sendResponse(200, ['message' => 'Post updated successfully']);
    }

    private function deletePost(?string $id): void
    {
        if ($id === null) {
            $this->sendResponse(400, ['error' => 'Invalid ID']);

            return;
        }
        $stmt = $this->db->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $this->sendResponse(200, ['message' => 'Post deleted successfully']);
    }

    private function sendResponse(int $statusCode, array $data): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function getRequestBody(): string
    {
        if ($this->requestBody === null) {
            $this->requestBody = file_get_contents('php://input') ?: '';
        }

        return $this->requestBody;
    }

    public function setRequestBody(string $body): void
    {
        $this->requestBody = $body;
    }
}
