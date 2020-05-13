<?php


declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190822071949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, api_token VARCHAR(180) DEFAULT NULL, last_login DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D6497BA2F5EB (api_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function postUp(Schema $schema): void
    {
        // ... update the entities
        $query = "SELECT * FROM fos_user";
        $stmt = $this->connection->prepare($query);
        $stmt->execute();

        // We can't use Doctrine's ORM to fetch the item, because it has a load of extra fields
        // that aren't in the entity definition.
        while ($row = $stmt->fetch()) {
            // But we will also get the entity, so that we can put addresses in it.
            $id = $row['id'];
            $username = $row['username'];
            $pass = $row['password'];
            $email = $row['email'];
            $enabled = $row['enabled'];
            $roles = $row['roles'];
            $last_login = $row['last_login'];

            preg_match_all('/"(?P<roles>.*?)"/', $roles, $matches);

            $roles = $matches['roles'];
            $roles = array_values($roles);
            $roles = json_encode($roles);

            $data = [
                'id' => $id,
                'username' => $username,
                'password' => $pass,
                'email' => $email,
                'enabled' => $enabled,
                'roles' => $roles,
                'last_login' => $last_login
            ];

            $this->connection->insert('user', $data);
        }

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user');
    }

}
