<?php
/* PDO 래퍼 — MySQL(카페24)과 SQLite(로컬)를 같은 코드로 다룬다.
   날짜는 전부 PHP 에서 'Y-m-d H:i:s' 문자열로 만들어 넣는다(CURDATE 등 방언 금지). */
class Database {
    private PDO $pdo;
    private string $driver;

    public function __construct() {
        $this->driver = DB_DRIVER;
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            if ($this->driver === 'sqlite') {
                $dir = dirname(DB_SQLITE_PATH);
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $this->pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $opt);
                $this->pdo->exec('PRAGMA journal_mode=WAL');
                $this->pdo->exec('PRAGMA busy_timeout=3000');
            } else {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
            }
        } catch (PDOException $e) {
            error_log('[DB] 연결 실패: ' . $e->getMessage());
            throw new Exception('데이터베이스 연결 실패');
        }
    }

    public function driver(): string { return $this->driver; }
    public function pdo(): PDO { return $this->pdo; }

    public function all(string $sql, array $params = []): array {
        $st = $this->pdo->prepare($sql); $st->execute($params); return $st->fetchAll();
    }
    public function one(string $sql, array $params = []): ?array {
        $st = $this->pdo->prepare($sql); $st->execute($params); $r = $st->fetch(); return $r === false ? null : $r;
    }
    public function run(string $sql, array $params = []): int {
        $st = $this->pdo->prepare($sql); $st->execute($params); return $st->rowCount();
    }
    public function value(string $sql, array $params = []) {
        $st = $this->pdo->prepare($sql); $st->execute($params); return $st->fetchColumn();
    }

    private static function col(string $name): string {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $name)) throw new Exception('잘못된 컬럼명');
        return "`$name`";
    }

    public function insert(string $table, array $data): int {
        if (!$data) throw new Exception('삽입할 데이터가 없습니다.');
        $cols = array_map([self::class, 'col'], array_keys($data));
        $ph = array_map(fn($k) => ":$k", array_keys($data));
        $sql = 'INSERT INTO ' . self::col($table) . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $ph) . ')';
        $st = $this->pdo->prepare($sql);
        foreach ($data as $k => $v) $st->bindValue(":$k", $v);
        $st->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int {
        if (!$data || !$where) throw new Exception('업데이트 조건이 없습니다.');
        $set = []; $params = [];
        foreach ($data as $k => $v) { $set[] = self::col($k) . " = :s_$k"; $params[":s_$k"] = $v; }
        [$w, $wp] = $this->whereClause($where); $params += $wp;
        return $this->run('UPDATE ' . self::col($table) . ' SET ' . implode(', ', $set) . $w, $params);
    }

    public function delete(string $table, array $where): int {
        if (!$where) throw new Exception('삭제 조건이 없습니다.');
        [$w, $wp] = $this->whereClause($where);
        return $this->run('DELETE FROM ' . self::col($table) . $w, $wp);
    }

    public function count(string $table, array $where = []): int {
        [$w, $wp] = $this->whereClause($where);
        return (int)$this->value('SELECT COUNT(*) FROM ' . self::col($table) . $w, $wp);
    }

    /* where: ['id' => 3] 또는 ['id' => ['in' => [1,2,3]]] */
    private function whereClause(array $where): array {
        if (!$where) return ['', []];
        $c = []; $p = [];
        foreach ($where as $k => $v) {
            if (is_array($v) && isset($v['in'])) {
                $ph = [];
                foreach (array_values($v['in']) as $i => $val) { $ph[] = ":w_{$k}_$i"; $p[":w_{$k}_$i"] = $val; }
                $c[] = self::col($k) . ' IN (' . implode(',', $ph ?: ['NULL']) . ')';
            } else {
                $c[] = self::col($k) . " = :w_$k"; $p[":w_$k"] = $v;
            }
        }
        return [' WHERE ' . implode(' AND ', $c), $p];
    }

    /* ── 스키마 ── */
    public function ensureSchema(): void {
        $sq = $this->driver === 'sqlite';
        $id = $sq ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $tail = $sq ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $text = $sq ? 'TEXT' : 'MEDIUMTEXT';
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS insights (
            id $id,
            slug VARCHAR(120) NOT NULL UNIQUE,
            category VARCHAR(60) NOT NULL DEFAULT '',
            title VARCHAR(255) NOT NULL DEFAULT '',
            meta_title VARCHAR(255) DEFAULT '',
            lead_text TEXT,
            excerpt TEXT,
            description TEXT,
            keywords VARCHAR(500) DEFAULT '',
            author VARCHAR(100) DEFAULT '글로파인드 전략팀',
            read_minutes INT NOT NULL DEFAULT 8,
            hero_image VARCHAR(500) DEFAULT '',
            hero_alt VARCHAR(255) DEFAULT '',
            summary_json TEXT,
            body_html $text,
            faq_json TEXT,
            links_json TEXT,
            cta_text VARCHAR(255) DEFAULT '',
            cta_label VARCHAR(100) DEFAULT '',
            is_published TINYINT NOT NULL DEFAULT 1,
            is_featured TINYINT NOT NULL DEFAULT 0,
            published_at DATETIME,
            views INT NOT NULL DEFAULT 0,
            created_at DATETIME,
            updated_at DATETIME
        )$tail");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS inquiries (
            id $id,
            company VARCHAR(255) DEFAULT '',
            name VARCHAR(255) DEFAULT '',
            email VARCHAR(255) DEFAULT '',
            phone VARCHAR(50) DEFAULT '',
            service VARCHAR(50) DEFAULT '',
            country VARCHAR(255) DEFAULT '',
            message TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            admin_note TEXT,
            source_page VARCHAR(500) DEFAULT '',
            ip_hash VARCHAR(64) DEFAULT '',
            user_agent VARCHAR(300) DEFAULT '',
            created_at DATETIME,
            processed_at DATETIME
        )$tail");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS visitor_stats (
            id $id,
            visit_date DATE NOT NULL,
            ip_hash VARCHAR(64) NOT NULL DEFAULT '',
            page_url VARCHAR(500) DEFAULT '',
            referer VARCHAR(500) DEFAULT '',
            user_agent VARCHAR(300) DEFAULT '',
            created_at DATETIME
        )$tail");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            skey VARCHAR(60) NOT NULL PRIMARY KEY,
            svalue TEXT
        )$tail");
        try { $this->pdo->exec('ALTER TABLE insights ADD COLUMN meta_title VARCHAR(255) DEFAULT \'\''); } catch (Exception $e) { /* 이미 있으면 무시 */ }
        foreach ([
            'CREATE INDEX IF NOT EXISTS idx_visit_date ON visitor_stats (visit_date)',
            'CREATE INDEX IF NOT EXISTS idx_inq_created ON inquiries (created_at)',
            'CREATE INDEX IF NOT EXISTS idx_inq_status ON inquiries (status)',
            'CREATE INDEX IF NOT EXISTS idx_ins_pub ON insights (is_published, published_at)',
        ] as $ix) {
            try { $this->pdo->exec($ix); } catch (Exception $e) { /* MySQL 구버전은 IF NOT EXISTS 미지원 — 이미 있으면 무시 */ }
        }
    }

    /* ── 설정 key/value ── */
    public function setting(string $key, $default = null) {
        $v = $this->value('SELECT svalue FROM settings WHERE skey = :k', [':k' => $key]);
        return ($v === false || $v === null) ? $default : $v;
    }
    public function setSetting(string $key, $value): void {
        if ($this->driver === 'sqlite') {
            $this->run('INSERT INTO settings (skey, svalue) VALUES (:k, :v) ON CONFLICT(skey) DO UPDATE SET svalue = excluded.svalue', [':k' => $key, ':v' => $value]);
        } else {
            $this->run('INSERT INTO settings (skey, svalue) VALUES (:k, :v) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [':k' => $key, ':v' => $value]);
        }
    }

    /* ── 방문 기록 ── */
    public static function ipHash(string $ip): string {
        return hash('sha256', $ip . '|' . date('Y-m-d') . '|' . APP_SALT);
    }
    public function recordVisit(string $page, string $referer, string $ua, string $ip): void {
        $this->insert('visitor_stats', [
            'visit_date' => date('Y-m-d'),
            'ip_hash'    => self::ipHash($ip),
            'page_url'   => mb_substr($page, 0, 500),
            'referer'    => mb_substr($referer, 0, 500),
            'user_agent' => mb_substr($ua, 0, 300),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

function get_db(): Database {
    static $db = null;
    if ($db === null) $db = new Database();
    return $db;
}
