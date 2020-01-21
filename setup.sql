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
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS blocked;

CREATE TABLE category(
  id   INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  PRIMARY KEY(id)
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
  PRIMARY KEY(id)
);

CREATE TABLE tag(
   category_id INT NOT NULL,
   resource_id INT NOT NULL,
   PRIMARY KEY(category_id, resource_id),
   FOREIGN KEY(category_id) REFERENCES category(id),
   FOREIGN KEY(resource_id) REFERENCES resource(id)
);

-- TO HANDLE BLOCKING ANYONE
CREATE TABLE blocked(
   netid VARCHAR(255) NOT NULL,
   PRIMARY KEY(netid)
);

CREATE TABLE access(
   code VARCHAR(6) NOT NULL,
   PRIMARY KEY(code)
);

-- DATA USED FOR TESTING
INSERT INTO category(name) VALUES
("Housing"),
("Food"),
("Academic");

INSERT INTO resource(name, link, description, icon, user) VALUES
("KOZ Apartments", "https://www.kozonmarket.com/", "cheap apartments for students.", "building", "em66@uw.edu"),
("UWT Pantry", "https://www.tacoma.uw.edu/thepantry", "free food for students", "utensils", "em66@uw.edu"),
("Tacoma Learning Center", "https://www.tacoma.uw.edu/teaching-learning-center/teaching-learning-center", "free tutoring for UWT students", "fa-study", "em66@uw.edu");

INSERT INTO tag VALUES (2, 2);
