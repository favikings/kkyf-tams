UPDATE members
SET date_of_birth = DATE_FORMAT(date_of_birth, '%m-%d')
WHERE date_of_birth IS NOT NULL
  AND date_of_birth REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$';

ALTER TABLE members
    MODIFY date_of_birth CHAR(5) NULL;
