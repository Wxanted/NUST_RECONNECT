-- Upgrade an existing database created from an older version of database.sql.
USE nust_reconnect;

-- Correct the original un-hashed seed credential if it is still present.
UPDATE Users
SET passwordHash = '$2y$10$wi1cYI16QsWx.PdW81aUQ.HQTzipdbb8CWHiipgr9ckrqxXCOh/.q'
WHERE email = 'admin@nust.local' AND passwordHash = 'admin2026';

-- Allow the current app statuses before converting old statuses/data.
ALTER TABLE Items
    MODIFY ItemStatus ENUM('pending', 'approved', 'rejected', 'active', 'resolved')
    NOT NULL DEFAULT 'active';

UPDATE Items
SET ItemStatus = 'active'
WHERE ItemStatus IN ('', 'pending', 'approved');

ALTER TABLE Items
    MODIFY ItemStatus ENUM('active', 'resolved', 'rejected')
    NOT NULL DEFAULT 'active';

ALTER TABLE Claims
    MODIFY ClaimStatus ENUM('pending', 'approved', 'rejected', 'completed', 'withdrawn')
    NOT NULL DEFAULT 'pending';

UPDATE Claims
SET ClaimStatus = 'completed'
WHERE ClaimStatus = 'approved';

ALTER TABLE Claims
    MODIFY ClaimStatus ENUM('pending', 'completed', 'rejected', 'withdrawn')
    NOT NULL DEFAULT 'pending';
