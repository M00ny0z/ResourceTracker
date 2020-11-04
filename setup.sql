/*
 *  Name: Manny Munoz
 *  Date: 01.14.19
 *  This is the setup sql file for the ResourceTracker database
 *  It creates all the tables to track resources, resource tags, those who are blocked from
 *  uploading, and resource categories.
 */
DROP TABLE IF EXISTS access;
DROP TABLE IF EXISTS tag;
DROP TABLE IF EXISTS resource;
DROP TABLE IF EXISTS unapproved_category;
DROP TABLE IF EXISTS category;

CREATE TABLE category(
  id   INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  PRIMARY KEY(id)
);

CREATE TABLE unapproved_category(
   category_id INT NOT NULL AUTO_INCREMENT,
   PRIMARY KEY(category_id),
   FOREIGN KEY(category_id) REFERENCES category(id)
);

-- USER TRACKS WHO ADDED THE RESOURCE
CREATE TABLE resource(
  id          INT NOT NULL AUTO_INCREMENT,
  name        VARCHAR(50) NOT NULL,
  link        VARCHAR(1000),
  description TEXT NOT NULL,
  icon        VARCHAR(100) NOT NULL,
  status      VARCHAR(20) NOT NULL DEFAULT "STANDBY",
  user        VARCHAR(255) NOT NULL,
  expire      DATE,
  PRIMARY KEY(id)
);

CREATE TABLE tag(
   category_id INT NOT NULL,
   resource_id INT NOT NULL,
   PRIMARY KEY(category_id, resource_id),
   FOREIGN KEY(category_id) REFERENCES category(id),
   FOREIGN KEY(resource_id) REFERENCES resource(id)
);

-- DATA USED FOR TESTING
INSERT INTO category(name) VALUES
("Housing"),
("Food"),
("Academic"),
("Legal Services"),
("Health"),
("Mental Health"),
("Childcare"),
("Financial"),
("Seasonal");
