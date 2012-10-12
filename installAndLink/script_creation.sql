CREATE DATABASE `testBase` ;
CREATE TABLE administrateurs(
id int( 10 ) unsigned NOT NULL AUTO_INCREMENT ,
nom_administrateur varchar( 32 ) NOT NULL ,
mot_de_passe char( 40 ) NOT NULL ,
PRIMARY KEY ( id ) ,
UNIQUE KEY nom_administrateur( nom_administrateur ) ,
KEY mot_de_passe( mot_de_passe )
)