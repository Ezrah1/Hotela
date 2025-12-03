<?php

namespace App\Repositories;

use PDO;

class LicenseRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function getActivation(?string $installationId = null): ?array
    {
        if (!$installationId) {
            $installationId = $this->getInstallationId();
        }

        $stmt = $this->db->prepare('
            SELECT * FROM license_activations 
            WHERE installation_id = :installation_id 
            AND status = "active"
            ORDER BY activated_at DESC
            LIMIT 1
        ');
        $stmt->execute(['installation_id' => $installationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getActivationAnyStatus(?string $installationId = null): ?array
    {
        if (!$installationId) {
            $installationId = $this->getInstallationId();
        }

        $stmt = $this->db->prepare('
            SELECT * FROM license_activations 
            WHERE installation_id = :installation_id 
            ORDER BY activated_at DESC
            LIMIT 1
        ');
        $stmt->execute(['installation_id' => $installationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function activate(string $installationId, string $directorEmail, int $directorUserId, string $licenseKey, string $signedToken, ?\DateTime $expiresAt = null, ?int $packageId = null, ?int $paymentId = null): int
    {
        // Check if license already exists for this installation
        $existingStmt = $this->db->prepare('SELECT id FROM license_activations WHERE installation_id = :installation_id LIMIT 1');
        $existingStmt->execute(['installation_id' => $installationId]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing license
            $expiresAtValue = $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null;
            
            $stmt = $this->db->prepare('
                UPDATE license_activations 
                SET director_email = :director_email,
                    director_user_id = :director_user_id,
                    license_key = :license_key,
                    signed_token = :signed_token,
                    expires_at = :expires_at,
                    status = "active",
                    package_id = :package_id,
                    payment_id = :payment_id,
                    activated_at = NOW()
                WHERE id = :id
            ');
            
            $params = [
                'id' => $existing['id'],
                'director_email' => $directorEmail,
                'director_user_id' => $directorUserId,
                'license_key' => $licenseKey,
                'signed_token' => $signedToken,
                'expires_at' => $expiresAtValue,
                'package_id' => $packageId,
                'payment_id' => $paymentId
            ];
            
            $stmt->execute($params);
            
            return (int)$existing['id'];
        } else {
            // Insert new license
            $stmt = $this->db->prepare('
                INSERT INTO license_activations (installation_id, director_email, director_user_id, license_key, signed_token, expires_at, status, package_id, payment_id)
                VALUES (:installation_id, :director_email, :director_user_id, :license_key, :signed_token, :expires_at, "active", :package_id, :payment_id)
            ');

            $stmt->execute([
                'installation_id' => $installationId,
                'director_email' => $directorEmail,
                'director_user_id' => $directorUserId,
                'license_key' => $licenseKey,
                'signed_token' => $signedToken,
                'expires_at' => $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null,
                'package_id' => $packageId,
                'payment_id' => $paymentId
            ]);

            return (int)$this->db->lastInsertId();
        }
    }
    
    public function updatePackage(int $licenseId, int $packageId, ?int $paymentId = null, ?\DateTime $expiresAt = null): bool
    {
        $fields = ['package_id = :package_id'];
        $params = ['license_id' => $licenseId, 'package_id' => $packageId];
        
        if ($paymentId !== null) {
            $fields[] = 'payment_id = :payment_id';
            $params['payment_id'] = $paymentId;
        }
        
        if ($expiresAt !== null) {
            $fields[] = 'expires_at = :expires_at';
            $params['expires_at'] = $expiresAt->format('Y-m-d H:i:s');
        }
        
        $sql = 'UPDATE license_activations SET ' . implode(', ', $fields) . ' WHERE id = :license_id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deactivate(string $installationId): void
    {
        $stmt = $this->db->prepare('
            UPDATE license_activations 
            SET status = "suspended" 
            WHERE installation_id = :installation_id
        ');
        $stmt->execute(['installation_id' => $installationId]);
    }

    public function verifyToken(string $installationId, string $signedToken): bool
    {
        $activation = $this->getActivation($installationId);
        if (!$activation) {
            return false;
        }

        return hash_equals($activation['signed_token'], $signedToken);
    }

    public function getInstallationId(): string
    {
        // Generate a unique installation ID based on server characteristics
        // This should be consistent across requests for the same installation
        $serverId = md5(
            ($_SERVER['SERVER_NAME'] ?? 'localhost') .
            ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) .
            (file_exists(BASE_PATH . '/.installation_id') ? file_get_contents(BASE_PATH . '/.installation_id') : '')
        );

        // Store it for consistency
        $idFile = BASE_PATH . '/.installation_id';
        if (!file_exists($idFile)) {
            file_put_contents($idFile, $serverId);
        }

        return $serverId;
    }

    public function isActivated(): bool
    {
        $activation = $this->getActivation();
        if (!$activation) {
            return false;
        }

        // Check if expired
        if ($activation['expires_at']) {
            $expiresAt = new \DateTime($activation['expires_at']);
            if ($expiresAt < new \DateTime()) {
                return false;
            }
        }

        return true;
    }

    public function revoke(int $licenseId, string $reason, int $revokedBy): bool
    {
        $stmt = $this->db->prepare('
            UPDATE license_activations 
            SET status = "revoked",
                revocation_reason = :reason,
                revoked_at = NOW(),
                revoked_by = :revoked_by
            WHERE id = :license_id
        ');
        
        return $stmt->execute([
            'license_id' => $licenseId,
            'reason' => $reason,
            'revoked_by' => $revokedBy
        ]);
    }

    public function getByDirectorUserId(int $directorUserId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT la.*, lp.name AS package_name, lp.price AS package_price, 
                   lp.duration_months AS package_duration, lp.features AS package_features,
                   lp.description AS package_description
            FROM license_activations la
            LEFT JOIN license_packages lp ON lp.id = la.package_id
            WHERE la.director_user_id = :director_user_id 
            AND la.status = "active"
            ORDER BY la.activated_at DESC
            LIMIT 1
        ');
        $stmt->execute(['director_user_id' => $directorUserId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}

