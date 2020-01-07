DROP TABLE IF EXISTS tag;
DROP TABLE IF EXISTS resource;
DROP TABLE IF EXISTS category;

CREATE TABLE category(
  id   INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  PRIMARY KEY(id)
);

CREATE TABLE resource(
  id          INT NOT NULL AUTO_INCREMENT,
  name        VARCHAR(50) NOT NULL,
  link        VARCHAR(1000),
  description TEXT NOT NULL,
  icon        VARCHAR(100) NOT NULL,
  status      VARCHAR(20) NOT NULL DEFAULT "STANDBY",
  PRIMARY KEY(id)
);

CREATE TABLE tag(
   category_id INT NOT NULL,
   resource_id INT NOT NULL,
   PRIMARY KEY(category_id, resource_id),
   FOREIGN KEY(category_id) REFERENCES category(id),
   FOREIGN KEY(resource_id) REFERENCES resource(id)
);
