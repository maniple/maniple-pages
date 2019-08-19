-- Store timestamps as BIGINT
ALTER TABLE pages MODIFY COLUMN created_at   BIGINT NOT NULL;
ALTER TABLE pages MODIFY COLUMN updated_at   BIGINT NOT NULL;
ALTER TABLE pages MODIFY COLUMN published_at BIGINT;
ALTER TABLE pages MODIFY COLUMN expires_at   BIGINT;
ALTER TABLE pages MODIFY COLUMN deleted_at   BIGINT;

ALTER TABLE page_revisions MODIFY COLUMN saved_at BIGINT NOT NULL;
