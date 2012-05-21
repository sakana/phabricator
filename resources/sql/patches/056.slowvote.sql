

CREATE TABLE {$NAMESPACE}_slowvote.slowvote_poll (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  phid VARCHAR(64) BINARY NOT NULL,
  UNIQUE KEY (phid),
  authorPHID VARCHAR(64) BINARY NOT NULL,
  responseVisibility INT UNSIGNED NOT NULL,
  shuffle INT UNSIGNED NOT NULL,
  method INT UNSIGNED NOT NULL,
  dateCreated INT UNSIGNED NOT NULL,
  dateModified INT UNSIGNED NOT NULL
);

CREATE TABLE {$NAMESPACE}_slowvote.slowvote_option (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pollID INT UNSIGNED NOT NULL,
  KEY (pollID),
  name VARCHAR(255) NOT NULL,
  dateCreated INT UNSIGNED NOT NULL,
  dateModified INT UNSIGNED NOT NULL
);

CREATE TABLE {$NAMESPACE}_slowvote.slowvote_comment (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pollID INT UNSIGNED NOT NULL,
  UNIQUE KEY (pollID, authorPHID),
  authorPHID VARCHAR(64) BINARY NOT NULL,
  commentText LONGBLOB NOT NULL,
  dateCreated INT UNSIGNED NOT NULL,
  dateModified INT UNSIGNED NOT NULL
);

CREATE TABLE {$NAMESPACE}_slowvote.slowvote_choice (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pollID INT UNSIGNED NOT NULL,
  KEY (pollID),
  optionID INT UNSIGNED NOT NULL,
  authorPHID VARCHAR(64) BINARY NOT NULL,
  KEY (authorPHID),
  dateCreated INT UNSIGNED NOT NULL,
  dateModified INT UNSIGNED NOT NULL
);