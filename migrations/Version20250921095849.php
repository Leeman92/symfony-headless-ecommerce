<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250921095849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE media_assets_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE order_items_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE orders_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE payments_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE product_media_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE product_variant_attributes_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE product_variants_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE products_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE categories (id INT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL, description TEXT DEFAULT NULL, is_active BOOLEAN NOT NULL, sort_order INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3AF34668989D9B62 ON categories (slug)');
        $this->addSql('CREATE INDEX IDX_3AF34668727ACA70 ON categories (parent_id)');
        $this->addSql('CREATE TABLE media_assets (id INT NOT NULL, url VARCHAR(512) NOT NULL, alt_text VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C5A8E750F47645AE ON media_assets (url)');
        $this->addSql('CREATE TABLE order_items (id INT NOT NULL, order_id INT NOT NULL, product_id INT NOT NULL, product_name VARCHAR(200) NOT NULL, product_sku VARCHAR(255) DEFAULT NULL, unit_price JSON NOT NULL, quantity INT NOT NULL, total_price JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_62809DB08D9F6D38 ON order_items (order_id)');
        $this->addSql('COMMENT ON COLUMN order_items.unit_price IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN order_items.total_price IS \'(DC2Type:money)\'');
        $this->addSql('CREATE TABLE orders (id INT NOT NULL, customer_id INT DEFAULT NULL, order_number VARCHAR(20) NOT NULL, guest_email VARCHAR(255) DEFAULT NULL, guest_phone VARCHAR(255) DEFAULT NULL, subtotal JSON NOT NULL, tax_amount JSON NOT NULL, shipping_amount JSON NOT NULL, discount_amount JSON NOT NULL, total JSON NOT NULL, status VARCHAR(20) NOT NULL, billing_address JSON DEFAULT NULL, shipping_address JSON DEFAULT NULL, metadata JSONB DEFAULT NULL, notes TEXT DEFAULT NULL, confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, shipped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, guest_first_name VARCHAR(100) DEFAULT NULL, guest_last_name VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEE551F0F81 ON orders (order_number)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE9395C3F3 ON orders (customer_id)');
        $this->addSql('COMMENT ON COLUMN orders.subtotal IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN orders.tax_amount IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN orders.shipping_amount IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN orders.discount_amount IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN orders.total IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN orders.billing_address IS \'(DC2Type:address)\'');
        $this->addSql('COMMENT ON COLUMN orders.shipping_address IS \'(DC2Type:address)\'');
        $this->addSql('COMMENT ON COLUMN orders.metadata IS \'(DC2Type:jsonb)\'');
        $this->addSql('CREATE TABLE payments (id INT NOT NULL, order_id INT NOT NULL, stripe_payment_intent_id VARCHAR(255) NOT NULL, stripe_payment_method_id VARCHAR(255) DEFAULT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, amount JSON NOT NULL, refunded_amount JSON NOT NULL, status VARCHAR(20) NOT NULL, payment_method VARCHAR(50) DEFAULT NULL, stripe_metadata JSONB DEFAULT NULL, payment_method_details JSONB DEFAULT NULL, failure_reason TEXT DEFAULT NULL, failure_code VARCHAR(255) DEFAULT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, failed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, refunded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65D29B32FC72F97E ON payments (stripe_payment_intent_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65D29B328D9F6D38 ON payments (order_id)');
        $this->addSql('COMMENT ON COLUMN payments.amount IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN payments.refunded_amount IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN payments.stripe_metadata IS \'(DC2Type:jsonb)\'');
        $this->addSql('COMMENT ON COLUMN payments.payment_method_details IS \'(DC2Type:jsonb)\'');
        $this->addSql('CREATE TABLE product_media (id INT NOT NULL, product_id INT NOT NULL, media_asset_id INT NOT NULL, is_primary BOOLEAN NOT NULL, position INT NOT NULL, alt_text_override VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CB70DA504584665A ON product_media (product_id)');
        $this->addSql('CREATE INDEX IDX_CB70DA50ABB37F3 ON product_media (media_asset_id)');
        $this->addSql('CREATE UNIQUE INDEX product_media_unique_asset ON product_media (product_id, media_asset_id)');
        $this->addSql('CREATE TABLE product_variant_attributes (id INT NOT NULL, variant_id INT NOT NULL, name VARCHAR(120) NOT NULL, value VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_31CCE7993B69A9AF ON product_variant_attributes (variant_id)');
        $this->addSql('CREATE UNIQUE INDEX variant_attribute_unique_name ON product_variant_attributes (variant_id, name)');
        $this->addSql('CREATE TABLE product_variants (id INT NOT NULL, product_id INT NOT NULL, sku VARCHAR(120) NOT NULL, name VARCHAR(255) NOT NULL, price JSON DEFAULT NULL, compare_price JSON DEFAULT NULL, stock INT DEFAULT NULL, is_default BOOLEAN NOT NULL, position INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_782839764584665A ON product_variants (product_id)');
        $this->addSql('CREATE UNIQUE INDEX product_variant_unique_sku ON product_variants (sku)');
        $this->addSql('COMMENT ON COLUMN product_variants.price IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN product_variants.compare_price IS \'(DC2Type:money)\'');
        $this->addSql('CREATE TABLE products (id INT NOT NULL, category_id INT NOT NULL, name VARCHAR(200) NOT NULL, slug VARCHAR(220) NOT NULL, description TEXT DEFAULT NULL, short_description TEXT DEFAULT NULL, price JSON NOT NULL, compare_price JSON DEFAULT NULL, stock INT NOT NULL, sku VARCHAR(255) DEFAULT NULL, is_active BOOLEAN NOT NULL, is_featured BOOLEAN NOT NULL, track_stock BOOLEAN NOT NULL, low_stock_threshold INT DEFAULT NULL, attributes JSONB DEFAULT NULL, seo_title VARCHAR(255) DEFAULT NULL, seo_description TEXT DEFAULT NULL, seo_keywords VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B3BA5A5A989D9B62 ON products (slug)');
        $this->addSql('CREATE INDEX IDX_B3BA5A5A12469DE2 ON products (category_id)');
        $this->addSql('COMMENT ON COLUMN products.price IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN products.compare_price IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN products.attributes IS \'(DC2Type:jsonb)\'');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, email VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, is_active BOOLEAN NOT NULL, is_verified BOOLEAN NOT NULL, last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668727ACA70 FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE9395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B328D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product_media ADD CONSTRAINT FK_CB70DA504584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product_media ADD CONSTRAINT FK_CB70DA50ABB37F3 FOREIGN KEY (media_asset_id) REFERENCES media_assets (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product_variant_attributes ADD CONSTRAINT FK_31CCE7993B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product_variants ADD CONSTRAINT FK_782839764584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE categories_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE media_assets_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE order_items_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE orders_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE payments_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE product_media_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE product_variant_attributes_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE product_variants_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE products_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE categories DROP CONSTRAINT FK_3AF34668727ACA70');
        $this->addSql('ALTER TABLE order_items DROP CONSTRAINT FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEE9395C3F3');
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT FK_65D29B328D9F6D38');
        $this->addSql('ALTER TABLE product_media DROP CONSTRAINT FK_CB70DA504584665A');
        $this->addSql('ALTER TABLE product_media DROP CONSTRAINT FK_CB70DA50ABB37F3');
        $this->addSql('ALTER TABLE product_variant_attributes DROP CONSTRAINT FK_31CCE7993B69A9AF');
        $this->addSql('ALTER TABLE product_variants DROP CONSTRAINT FK_782839764584665A');
        $this->addSql('ALTER TABLE products DROP CONSTRAINT FK_B3BA5A5A12469DE2');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE media_assets');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE product_media');
        $this->addSql('DROP TABLE product_variant_attributes');
        $this->addSql('DROP TABLE product_variants');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE users');
    }
}
