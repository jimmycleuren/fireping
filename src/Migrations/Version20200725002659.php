<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200725002659 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_slavegroups DROP FOREIGN KEY FK_C1AC347C29D09A3');
        $this->addSql('ALTER TABLE device_slavegroups ADD CONSTRAINT FK_C1AC347C29D09A3 FOREIGN KEY (slavegroup_id) REFERENCES slave_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_probes DROP FOREIGN KEY FK_137229A93D2D0D4A');
        $this->addSql('ALTER TABLE device_probes DROP FOREIGN KEY FK_137229A994A4C7D4');
        $this->addSql('ALTER TABLE device_probes ADD CONSTRAINT FK_137229A93D2D0D4A FOREIGN KEY (probe_id) REFERENCES probe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_probes ADD CONSTRAINT FK_137229A994A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_alert_rules DROP FOREIGN KEY FK_11403A1194A4C7D4');
        $this->addSql('ALTER TABLE device_alert_rules DROP FOREIGN KEY FK_11403A11EA1DA493');
        $this->addSql('ALTER TABLE device_alert_rules ADD CONSTRAINT FK_11403A1194A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_alert_rules ADD CONSTRAINT FK_11403A11EA1DA493 FOREIGN KEY (alert_rule_id) REFERENCES alert_rule (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_alert_destinations DROP FOREIGN KEY FK_A760D66C94A4C7D4');
        $this->addSql('ALTER TABLE device_alert_destinations DROP FOREIGN KEY FK_A760D66CFCDBED38');
        $this->addSql('ALTER TABLE device_alert_destinations ADD CONSTRAINT FK_A760D66C94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_alert_destinations ADD CONSTRAINT FK_A760D66CFCDBED38 FOREIGN KEY (alert_destination_id) REFERENCES alert_destination (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_slavegroups DROP FOREIGN KEY FK_78BDA082115F0EE5');
        $this->addSql('ALTER TABLE domain_slavegroups DROP FOREIGN KEY FK_78BDA082C29D09A3');
        $this->addSql('ALTER TABLE domain_slavegroups ADD CONSTRAINT FK_78BDA082115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_slavegroups ADD CONSTRAINT FK_78BDA082C29D09A3 FOREIGN KEY (slavegroup_id) REFERENCES slave_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_probes DROP FOREIGN KEY FK_AF01547B115F0EE5');
        $this->addSql('ALTER TABLE domain_probes DROP FOREIGN KEY FK_AF01547B3D2D0D4A');
        $this->addSql('ALTER TABLE domain_probes ADD CONSTRAINT FK_AF01547B115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_probes ADD CONSTRAINT FK_AF01547B3D2D0D4A FOREIGN KEY (probe_id) REFERENCES probe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_alert_rules DROP FOREIGN KEY FK_65E759D4115F0EE5');
        $this->addSql('ALTER TABLE domain_alert_rules DROP FOREIGN KEY FK_65E759D4EA1DA493');
        $this->addSql('ALTER TABLE domain_alert_rules ADD CONSTRAINT FK_65E759D4115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_alert_rules ADD CONSTRAINT FK_65E759D4EA1DA493 FOREIGN KEY (alert_rule_id) REFERENCES alert_rule (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_alert_destinations DROP FOREIGN KEY FK_917F6FF9115F0EE5');
        $this->addSql('ALTER TABLE domain_alert_destinations DROP FOREIGN KEY FK_917F6FF9FCDBED38');
        $this->addSql('ALTER TABLE domain_alert_destinations ADD CONSTRAINT FK_917F6FF9115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_alert_destinations ADD CONSTRAINT FK_917F6FF9FCDBED38 FOREIGN KEY (alert_destination_id) REFERENCES alert_destination (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_alert_destinations DROP FOREIGN KEY FK_A760D66C94A4C7D4');
        $this->addSql('ALTER TABLE device_alert_destinations DROP FOREIGN KEY FK_A760D66CFCDBED38');
        $this->addSql('ALTER TABLE device_alert_destinations ADD CONSTRAINT FK_A760D66C94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_alert_destinations ADD CONSTRAINT FK_A760D66CFCDBED38 FOREIGN KEY (alert_destination_id) REFERENCES alert_destination (id)');
        $this->addSql('ALTER TABLE device_alert_rules DROP FOREIGN KEY FK_11403A1194A4C7D4');
        $this->addSql('ALTER TABLE device_alert_rules DROP FOREIGN KEY FK_11403A11EA1DA493');
        $this->addSql('ALTER TABLE device_alert_rules ADD CONSTRAINT FK_11403A1194A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_alert_rules ADD CONSTRAINT FK_11403A11EA1DA493 FOREIGN KEY (alert_rule_id) REFERENCES alert_rule (id)');
        $this->addSql('ALTER TABLE device_probes DROP FOREIGN KEY FK_137229A994A4C7D4');
        $this->addSql('ALTER TABLE device_probes DROP FOREIGN KEY FK_137229A93D2D0D4A');
        $this->addSql('ALTER TABLE device_probes ADD CONSTRAINT FK_137229A994A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_probes ADD CONSTRAINT FK_137229A93D2D0D4A FOREIGN KEY (probe_id) REFERENCES probe (id)');
        $this->addSql('ALTER TABLE device_slavegroups DROP FOREIGN KEY FK_C1AC347C29D09A3');
        $this->addSql('ALTER TABLE device_slavegroups ADD CONSTRAINT FK_C1AC347C29D09A3 FOREIGN KEY (slavegroup_id) REFERENCES slave_group (id)');
        $this->addSql('ALTER TABLE domain_alert_destinations DROP FOREIGN KEY FK_917F6FF9115F0EE5');
        $this->addSql('ALTER TABLE domain_alert_destinations DROP FOREIGN KEY FK_917F6FF9FCDBED38');
        $this->addSql('ALTER TABLE domain_alert_destinations ADD CONSTRAINT FK_917F6FF9115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_alert_destinations ADD CONSTRAINT FK_917F6FF9FCDBED38 FOREIGN KEY (alert_destination_id) REFERENCES alert_destination (id)');
        $this->addSql('ALTER TABLE domain_alert_rules DROP FOREIGN KEY FK_65E759D4115F0EE5');
        $this->addSql('ALTER TABLE domain_alert_rules DROP FOREIGN KEY FK_65E759D4EA1DA493');
        $this->addSql('ALTER TABLE domain_alert_rules ADD CONSTRAINT FK_65E759D4115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_alert_rules ADD CONSTRAINT FK_65E759D4EA1DA493 FOREIGN KEY (alert_rule_id) REFERENCES alert_rule (id)');
        $this->addSql('ALTER TABLE domain_probes DROP FOREIGN KEY FK_AF01547B115F0EE5');
        $this->addSql('ALTER TABLE domain_probes DROP FOREIGN KEY FK_AF01547B3D2D0D4A');
        $this->addSql('ALTER TABLE domain_probes ADD CONSTRAINT FK_AF01547B115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_probes ADD CONSTRAINT FK_AF01547B3D2D0D4A FOREIGN KEY (probe_id) REFERENCES probe (id)');
        $this->addSql('ALTER TABLE domain_slavegroups DROP FOREIGN KEY FK_78BDA082115F0EE5');
        $this->addSql('ALTER TABLE domain_slavegroups DROP FOREIGN KEY FK_78BDA082C29D09A3');
        $this->addSql('ALTER TABLE domain_slavegroups ADD CONSTRAINT FK_78BDA082115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_slavegroups ADD CONSTRAINT FK_78BDA082C29D09A3 FOREIGN KEY (slavegroup_id) REFERENCES slave_group (id)');
    }
}
