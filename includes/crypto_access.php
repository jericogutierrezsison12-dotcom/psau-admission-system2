<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/encryption.php';

class CryptoAccess {
    private PDO $conn;
    public function __construct(PDO $conn) { $this->conn = $conn; }

    // USERS
    public function insertUser(array $data): int {
        $sql = "INSERT INTO users (control_number, first_name, last_name, email, mobile_number, password, is_verified, gender, birth_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data['control_number'] ?? null,
            enc_personal($data['first_name'] ?? ''),
            enc_personal($data['last_name'] ?? ''),
            enc_contact($data['email'] ?? ''),
            enc_contact($data['mobile_number'] ?? ''),
            $data['password'] ?? '',
            $data['is_verified'] ?? 0,
            enc_personal($data['gender'] ?? ''),
            enc_personal($data['birth_date'] ?? '')
        ]);
        return intval($this->conn->lastInsertId());
    }

    public function getUserDecrypted(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) return null;
        $u['first_name'] = dec_personal($u['first_name'] ?? '');
        $u['last_name'] = dec_personal($u['last_name'] ?? '');
        $u['email'] = dec_contact($u['email'] ?? '');
        $u['mobile_number'] = dec_contact($u['mobile_number'] ?? '');
        $u['gender'] = dec_personal($u['gender'] ?? '');
        $u['birth_date'] = dec_personal($u['birth_date'] ?? '');
        return $u;
    }

    public function updateUserEncrypted(int $id, array $data): bool {
        $fields = [];
        $vals = [];
        $map = [
            'first_name' => 'personal',
            'last_name' => 'personal',
            'email' => 'contact',
            'mobile_number' => 'contact',
            'address' => 'personal',
            'gender' => 'personal',
            'birth_date' => 'personal'
        ];
        foreach ($map as $k => $ctx) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $vals[] = $ctx === 'contact' ? enc_contact($data[$k]) : enc_personal($data[$k]);
            }
        }
        if (isset($data['password'])) { $fields[] = 'password = ?'; $vals[] = $data['password']; }
        if (isset($data['is_verified'])) { $fields[] = 'is_verified = ?'; $vals[] = $data['is_verified']; }
        if (!$fields) return true;
        $vals[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($vals);
    }
}

// global instance helper if needed
$crypto = new CryptoAccess($conn);
?>


