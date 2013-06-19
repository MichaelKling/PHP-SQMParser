SQMParser
========

> SQMParser is a PHP based parser for reading OFP/ArmA/ArmA2 Mission Files and parsing its contents.


Get Started
-----------

Look at the example.php to find out how to use the parser.

There are 3 Levels:
1. Lexer: Reading the Mission File and splitting it into atomic Tokens
2. Parser: Transforming the tokenstream into a PHP Datatype representing the contents of the mission.sqm
3. PlayerParser: Finding player slots which will be also displayed in the ArmA Multiplayer Setup Screen

Classnames
---------

Currently the system comes with classname translations for 
* ACE 2
* BWMod
* I44
* ArmA2
* ArmA2 OA and Addons

If you want to add classnames you can do that by putting a json or csv formatted file in the directories 

* /classnames/men
* /classnames/vehicles

A csv file needs the classname as the first value and the real world name as the second value.

JSON files need to provide an array in which every item has a "name" attribute for the classname and a "displayname" for the real world name.


Contact
---------

![Michael Kling](http://www.klingit.de/images/mkling.jpg)

[Website](http://www.klingit.de/)

Michael Kling

Schnurstrasse 35

61231 Bad Nauheim

Telefon: +49 (0) 6032 / 7848617

Fax: +49 (0) 6032 / 7848617

Mobil: +49 (0) 157 39392459

E-Mail: michael.kling@klingit.de
