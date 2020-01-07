DROP TABLE IF EXISTS resource;
DROP TABLE IF EXISTS category;

CREATE TABLE category(
  id   INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE resource(
  id          NOT NULL AUTO_INCREMENT
  name        VARCHAR(50) NOT NULL,
  link        VARCHAR(1000) NOT NULL,
  description TEXT NOT NULL,
  icon        VARCHAR(100) NOT NULL,
  category_id INT NOT NULL,
  status      VARCHAR(20) NOT NULL DEFAULT "STANDBY",
  PRIMARY KEY(id),
  FOREIGN KEY(category_id) REFERENCES category(id)
);
