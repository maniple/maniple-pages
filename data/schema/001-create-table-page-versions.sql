-- noinspection SqlResolveForFile

CREATE TABLE page_versions (

    page_version_id     INT PRIMARY KEY AUTO_INCREMENT,

    page_id             INT NOT NULL,

    markup_type         VARCHAR(32) NOT NULL,

    title               VARCHAR(191) NOT NULL,

    excerpt             TEXT,

    content             TEXT,

    FOREIGN KEY page_versions_page_id_fkey (page_id) REFERENCES page_versions (page_id)

) ENGINE=InnoDB CHARACTER SET utf8mb4;


ALTER TABLE pages ADD CONSTRAINT pages_latest_version_id_fkey
    FOREIGN KEY (page_id, latest_version_id)
    REFERENCES page_versions (page_id, page_version_id);


ALTER TABLE pages ADD CONSTRAINT pages_published_version_id_fkey
    FOREIGN KEY (page_id, published_version_id)
    REFERENCES page_versions (page_id, page_version_id);
