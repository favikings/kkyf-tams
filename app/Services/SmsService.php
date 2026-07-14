<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use PDO;
use RuntimeException;

final class SmsService
{
    private PDO $pdo;

    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
        $this->config = (array) $config->get('sms', []);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function membersForUser(array $user): array
    {
        $params = [];
        $sql = "SELECT m.id, m.full_name, m.phone, t.name AS tent_name
                FROM members m
                JOIN tents t ON t.id = m.tent_id
                WHERE m.active_status = 'active'
                  AND m.phone IS NOT NULL
                  AND m.phone <> ''";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND m.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $sql .= ' ORDER BY m.full_name ASC LIMIT 250';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function tentsForUser(array $user): array
    {
        $params = [];
        $sql = "SELECT t.id,
                       t.name,
                       (
                           SELECT COUNT(*)
                           FROM members m
                           WHERE m.tent_id = t.id
                             AND m.active_status = 'active'
                             AND m.phone IS NOT NULL
                             AND m.phone <> ''
                       ) AS sms_member_count
                FROM tents t";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' WHERE t.id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        } else {
            $sql .= "
                WHERE t.id IN (
                    SELECT DISTINCT target_id
                    FROM migration_logs
                    WHERE source_table = 'legacy_tents'
                      AND target_table = 'tents'
                      AND status IN ('success', 'skipped')
                      AND target_id IS NOT NULL
                )";
        }

        $sql .= ' ORDER BY t.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function recentLogsForUser(array $user): array
    {
        if (!$this->tableExists('sms_logs')) {
            return [];
        }

        $params = [];
        $sql = "SELECT sl.*,
                       actor.full_name AS actor_name,
                       m.full_name AS member_name,
                       t.name AS tent_name
                FROM sms_logs sl
                JOIN users actor ON actor.id = sl.user_id
                LEFT JOIN members m ON m.id = sl.member_id
                LEFT JOIN tents t ON t.id = sl.tent_id";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' WHERE (sl.tent_id = ? OR sl.member_id IN (SELECT id FROM members WHERE tent_id = ?))';
            $params[] = (int) ($user['tent_id'] ?? 0);
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $sql .= ' ORDER BY sl.created_at DESC LIMIT 20';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>
     */
    public function modeSummary(): array
    {
        $driver = (string) ($this->config['driver'] ?? 'log_only');
        $enabled = (bool) ($this->config['enabled'] ?? false);
        $isLive = $enabled && $driver === 'africastalking';

        if ($isLive) {
            return [
                'is_live' => true,
                'label' => 'Live SMS',
                'message' => 'Messages are configured to send through the active provider.',
            ];
        }

        return [
            'is_live' => false,
            'label' => 'Simulation Mode',
            'message' => 'SMS actions are being logged locally only until the live provider is enabled.',
        ];
    }

    /**
     * @param array<string, mixed> $user
     */
    public function send(array $user, string $scope, ?int $memberId, ?int $tentId, string $message): void
    {
        if (!$this->tableExists('sms_logs')) {
            throw new RuntimeException('Run the Phase 10 sms_logs migration before sending SMS.');
        }

        $message = trim($message);
        if ($message === '') {
            throw new RuntimeException('SMS message cannot be empty.');
        }

        $messageLength = function_exists('mb_strlen') ? mb_strlen($message) : strlen($message);
        if ($messageLength > 480) {
            throw new RuntimeException('SMS message must be 480 characters or fewer.');
        }

        $scope = in_array($scope, ['member', 'tent', 'bulk'], true) ? $scope : 'member';
        if ($scope === 'bulk' && ($user['role'] ?? null) !== 'Super Admin') {
            throw new RuntimeException('Only Super Admin can send bulk SMS.');
        }

        [$recipients, $resolvedMemberId, $resolvedTentId] = match ($scope) {
            'member' => $this->recipientsForMember($user, (int) $memberId),
            'tent' => $this->recipientsForTent($user, (int) $tentId),
            'bulk' => $this->recipientsForBulk($user),
        };

        if ($recipients === []) {
            throw new RuntimeException('No valid phone recipients were found for this SMS.');
        }

        $result = $this->dispatchMessage($recipients, $message);
        $this->recordLog(
            (int) ($user['id'] ?? 0),
            $scope,
            $resolvedMemberId,
            $resolvedTentId,
            $recipients,
            $message,
            $result
        );
    }

    /**
     * @param array<int, array{phone:string,name:string}> $recipients
     * @return array<string, mixed>
     */
    private function dispatchMessage(array $recipients, string $message): array
    {
        $driver = (string) ($this->config['driver'] ?? 'log_only');
        $enabled = (bool) ($this->config['enabled'] ?? false);

        if (!$enabled || $driver === 'log_only') {
            return [
                'provider' => 'log_only',
                'status' => 'simulated',
                'provider_message_id' => null,
                'response_payload' => [
                    'mode' => 'log_only',
                    'recipients' => count($recipients),
                ],
            ];
        }

        if ($driver !== 'africastalking') {
            throw new RuntimeException('Unsupported SMS driver configured.');
        }

        return $this->sendViaAfricasTalking($recipients, $message);
    }

    /**
     * @param array<int, array{phone:string,name:string}> $recipients
     * @return array<string, mixed>
     */
    private function sendViaAfricasTalking(array $recipients, string $message): array
    {
        $provider = (array) ($this->config['africastalking'] ?? []);
        $username = trim((string) ($provider['username'] ?? ''));
        $apiKey = trim((string) ($provider['api_key'] ?? ''));
        $endpoint = trim((string) ($provider['endpoint'] ?? ''));
        $senderId = trim((string) ($this->config['default_sender'] ?? ''));

        if ($username === '' || $apiKey === '' || $endpoint === '') {
            throw new RuntimeException('AfricasTalking credentials are incomplete.');
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL is required for AfricasTalking SMS.');
        }

        $payload = [
            'username' => $username,
            'to' => implode(',', array_column($recipients, 'phone')),
            'message' => $message,
        ];

        if ($senderId !== '') {
            $payload['from'] = $senderId;
        }

        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'apiKey: ' . $apiKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $error !== '') {
            throw new RuntimeException('AfricasTalking request failed: ' . $error);
        }

        $decoded = json_decode((string) $body, true);
        if ($httpCode >= 400) {
            throw new RuntimeException('AfricasTalking rejected the SMS request.');
        }

        $providerMessageId = null;
        if (is_array($decoded)
            && isset($decoded['SMSMessageData']['Recipients'][0]['messageId'])) {
            $providerMessageId = (string) $decoded['SMSMessageData']['Recipients'][0]['messageId'];
        }

        return [
            'provider' => 'africastalking',
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'response_payload' => is_array($decoded) ? $decoded : ['raw' => (string) $body],
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array{0: array<int, array{phone:string,name:string}>, 1: int|null, 2: int|null}
     */
    private function recipientsForMember(array $user, int $memberId): array
    {
        if ($memberId <= 0) {
            throw new RuntimeException('Choose a member recipient.');
        }

        $params = [$memberId];
        $sql = "SELECT id, full_name, phone, tent_id
                FROM members
                WHERE id = ?
                  AND active_status = 'active'
                  AND phone IS NOT NULL
                  AND phone <> ''";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $member = $stmt->fetch();

        if (!$member) {
            throw new RuntimeException('Selected member is unavailable for SMS.');
        }

        return [[
            [
                'phone' => (string) $member['phone'],
                'name' => (string) $member['full_name'],
            ],
        ], (int) $member['id'], (int) $member['tent_id']];
    }

    /**
     * @param array<string, mixed> $user
     * @return array{0: array<int, array{phone:string,name:string}>, 1: int|null, 2: int|null}
     */
    private function recipientsForTent(array $user, int $tentId): array
    {
        if ($tentId <= 0) {
            throw new RuntimeException('Choose a tent recipient group.');
        }

        if (($user['role'] ?? null) === 'Tent Admin' && (int) ($user['tent_id'] ?? 0) !== $tentId) {
            throw new RuntimeException('Tent Admin can only send SMS inside the assigned tent.');
        }

        $stmt = $this->pdo->prepare(
            "SELECT full_name, phone
             FROM members
             WHERE tent_id = ?
               AND active_status = 'active'
               AND phone IS NOT NULL
               AND phone <> ''
             ORDER BY full_name ASC"
        );
        $stmt->execute([$tentId]);
        $rows = $stmt->fetchAll();

        return [$this->mapRecipients($rows), null, $tentId];
    }

    /**
     * @param array<string, mixed> $user
     * @return array{0: array<int, array{phone:string,name:string}>, 1: int|null, 2: int|null}
     */
    private function recipientsForBulk(array $user): array
    {
        if (($user['role'] ?? null) !== 'Super Admin') {
            throw new RuntimeException('Only Super Admin can send bulk SMS.');
        }

        $stmt = $this->pdo->query(
            "SELECT full_name, phone
             FROM members
             WHERE active_status = 'active'
               AND phone IS NOT NULL
               AND phone <> ''
             ORDER BY full_name ASC"
        );

        return [$this->mapRecipients($stmt->fetchAll()), null, null];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{phone:string,name:string}>
     */
    private function mapRecipients(array $rows): array
    {
        $recipients = [];

        foreach ($rows as $row) {
            $phone = trim((string) ($row['phone'] ?? ''));
            if ($phone === '') {
                continue;
            }

            $recipients[] = [
                'phone' => $phone,
                'name' => trim((string) ($row['full_name'] ?? 'Recipient')),
            ];
        }

        return $recipients;
    }

    /**
     * @param array<int, array{phone:string,name:string}> $recipients
     * @param array<string, mixed> $result
     */
    private function recordLog(
        int $userId,
        string $scope,
        ?int $memberId,
        ?int $tentId,
        array $recipients,
        string $message,
        array $result
    ): void {
        $statement = $this->pdo->prepare(
            "INSERT INTO sms_logs (
                uuid, user_id, scope, member_id, tent_id, recipient_count,
                recipients_snapshot, message, provider, provider_message_id,
                status, response_payload, sent_at
             ) VALUES (
                UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
             )"
        );

        $statement->execute([
            $userId,
            $scope,
            $memberId,
            $tentId,
            count($recipients),
            json_encode($recipients, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $message,
            (string) ($result['provider'] ?? 'log_only'),
            $result['provider_message_id'] ?? null,
            (string) ($result['status'] ?? 'failed'),
            json_encode($result['response_payload'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function tableExists(string $table): bool
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = ?"
        );
        $statement->execute([$table]);

        return (int) $statement->fetchColumn() > 0;
    }
}
