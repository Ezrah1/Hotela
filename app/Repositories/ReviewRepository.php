<?php

namespace App\Repositories;

use PDO;

class ReviewRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO reviews (
                reservation_id, guest_name, guest_email, guest_phone,
                rating, title, comment, category, status
            ) VALUES (
                :reservation_id, :guest_name, :guest_email, :guest_phone,
                :rating, :title, :comment, :category, :status
            )
        ');

        $stmt->execute([
            'reservation_id' => $data['reservation_id'] ?? null,
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'],
            'guest_phone' => $data['guest_phone'] ?? null,
            'rating' => (int)$data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'] ?? null,
            'category' => $data['category'] ?? 'overall',
            'status' => $data['status'] ?? 'pending',
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT reviews.*, 
                   reservations.reference as reservation_reference,
                   reservations.check_in,
                   reservations.check_out
            FROM reviews
            LEFT JOIN reservations ON reservations.id = reviews.reservation_id
            WHERE reviews.id = :id
        ');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function listForGuest(string $identifier): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        $params = [];
        $conditions = [];

        if (str_contains($identifier, '@')) {
            $params['guest_email'] = strtolower($identifier);
            $conditions[] = 'LOWER(reviews.guest_email) = :guest_email';
        } else {
            $sanitized = preg_replace('/[^0-9]/', '', $identifier);
            if ($sanitized === '') {
                return [];
            }
            $params['guest_phone'] = $sanitized;
            $conditions[] = 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(reviews.guest_phone, " ", ""), "-", ""), "(", ""), ")", ""), "+", "") = :guest_phone';
        }

        $sql = '
            SELECT reviews.*, 
                   reservations.reference as reservation_reference,
                   reservations.check_in,
                   reservations.check_out
            FROM reviews
            LEFT JOIN reservations ON reservations.id = reviews.reservation_id
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY reviews.created_at DESC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getApproved(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = ['status' => 'approved'];
        $conditions = ['reviews.status = :status'];

        if (!empty($filters['category'])) {
            $params['category'] = $filters['category'];
            $conditions[] = 'reviews.category = :category';
        }

        if (!empty($filters['min_rating'])) {
            $params['min_rating'] = (int)$filters['min_rating'];
            $conditions[] = 'reviews.rating >= :min_rating';
        }

        $sql = '
            SELECT reviews.*, 
                   reservations.reference as reservation_reference
            FROM reviews
            LEFT JOIN reservations ON reservations.id = reviews.reservation_id
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY reviews.created_at DESC
            LIMIT :limit OFFSET :offset
        ';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getAverageRating(?string $category = null): float
    {
        $params = ['status' => 'approved'];
        $sql = 'SELECT AVG(rating) as avg_rating FROM reviews WHERE status = :status';

        if ($category) {
            $params['category'] = $category;
            $sql .= ' AND category = :category';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ? (float)round($result['avg_rating'], 1) : 0.0;
    }

    public function getRatingCount(?string $category = null): int
    {
        $params = ['status' => 'approved'];
        $sql = 'SELECT COUNT(*) as count FROM reviews WHERE status = :status';

        if ($category) {
            $params['category'] = $category;
            $sql .= ' AND category = :category';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ? (int)$result['count'] : 0;
    }
}

