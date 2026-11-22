ALTER TABLE verifications
    ADD COLUMN submission_key CHAR(64) NULL,
    ADD COLUMN submission_hash CHAR(64) NULL,
    ADD CONSTRAINT uq_verifications_submission_key UNIQUE (submission_key);
