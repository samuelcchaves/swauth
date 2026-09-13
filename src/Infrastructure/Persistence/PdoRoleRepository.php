<?php 

namespace SwAuth\Infrastructure\Persistence;

use ErrorException;
use PDOException;
use PDO;
use SwAuth\Domain\Repositories\RoleRepositoryInterface;
use SwAuth\Domain\Entities\Role;


class PdoRoleRepository implements RoleRepositoryInterface {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Role {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if($row === false){
            return null;   
        }

        return $this->mapRowToRole($row);
    }


    public function findByName(string $roleName): ?Role {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE role_name = ?");
        $stmt->execute([$roleName]);
        $row = $stmt->fetch();

        if($row === false) {
            return null;
        }

        return $this->mapRowToRole($row);
    }

    public function findAll(): array {
        $stmt = $this->pdo->prepare("SELECT * FROM roles");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        
        $roles = [];
        foreach($rows as $row) {
            $roles[] = $this->mapRowToRole($row);
        }

        return $roles;
    }

    private function mapRowToRole(array $row): Role {
        return new Role(
            id: (int) $row['id'],
            roleName: $row['role_name'],
            level: (int) $row['role_level'],
            requiresMfa: (bool) $row['requires_mfa'],
            roleDescription: $row["role_description"]
        );
    }

}