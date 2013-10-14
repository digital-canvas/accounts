PRAGMA foreign_keys = OFF;

-- ----------------------------
-- Table structure for domains"
-- ----------------------------
CREATE TABLE "domains" (
"id"  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
"name"  TEXT(255),
"domain"  TEXT(255),
"url"  TEXT(255),
"notes"  TEXT
);

-- ----------------------------
-- Indexes structure for table domains
-- ----------------------------
CREATE INDEX "search" ON "domains" ("name" ASC, "domain" ASC, "url" ASC);


-- ----------------------------
-- Table structure for data"
-- ----------------------------
CREATE TABLE "data" (
"id"  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
"domain"  INTEGER NOT NULL,
"group"  TEXT(255) NOT NULL,
"name"  TEXT(255) NOT NULL,
"value"  TEXT,
CONSTRAINT "domains" FOREIGN KEY ("domain") REFERENCES "domains" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

-- ----------------------------
-- Indexes structure for table data
-- ----------------------------
CREATE INDEX "data" ON "data" ("group" ASC);

