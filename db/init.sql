-- Existing application user information
CREATE TABLE users (
	id INT PRIMARY KEY auto_increment,
	login VARCHAR(32) NOT NULL,
	password VARCHAR(32) NOT NULL,
	fullname TINYTEXT,
	favorite_colour ENUM ('red','green','blue','yellow')
);

-- Existing application session storage
CREATE TABLE sessions (
	hash VARCHAR(32) PRIMARY KEY,
	user_id INT,
	expires INT, -- timestamp
	FOREIGN KEY (user_id) REFERENCES users (id)
);


-- One user_id may have several name_id.

-- Re: howto, I don't see how placing the identity dump in the 'users'
-- table can work: if more than one name identifier match one
-- application user, then several corresponding identity dumps will
-- match that user as well. Let's put the identity dump in this table
-- - as done in the SPIP demo.

-- TODO: identity dump may carry additional information in the case of
-- "federation" - to check. Currently we'll store it as well, even
-- though we won't make use of it in the source code. In principle
-- there should be another 'liberty_ids' table containing the name_id
-- and associated id_dump, but for simplicity we just store
-- liberty_id_dump here directly.
CREATE TABLE users_to_liberty (
	user_id INT,
	liberty_name_id VARCHAR(33),
	liberty_id_dump TEXT NOT NULL,
	PRIMARY KEY (user_id, liberty_name_id),
	FOREIGN KEY (user_id) REFERENCES id (users) ON DELETE CASCADE
);

-- Keep the session dump, it will be used to initiate the Single
-- Logout Out (SLO)

-- Re: howto, I think it's better to associate the application session
-- with the liberty session, rather than the liberty name id. On
-- logout, we don't want to kill all a name_id's session, only the
-- current one.

-- liberty_id_dump sounds optional, but it's good to keep it and pass
-- it to $login->setIdentifyFromDump() before calling
-- $login->acceptSso()
CREATE TABLE sessions_liberty (
	session_hash VARCHAR(32) PRIMARY KEY,
	liberty_session_dump TEXT NOT NULL,
	liberty_name_id VARCHAR(33) NOT NULL,
	FOREIGN KEY (session_hash) REFERENCES sessions (hash) ON DELETE CASCADE,
	FOREIGN KEY (liberty_name_id) REFERENCES users_to_liberty (liberty_name_id) ON DELETE CASCADE
);
