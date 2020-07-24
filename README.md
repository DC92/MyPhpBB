Dominique92.GeoBB
=================
- GeoBB is a map extension for PhpBB 3.2+ forums associating points, lines or surfaces on a map to a topic in a forum.
- Each forum groups elements of the same category (same icon on the map).
- Each topic or comment in the forum can be represented by an icon, lines or areas on the map.

DEMO
====
Website software of https://alpages.info

DEPENDENCIES
============
* This package includes : https://github.com/Dominique92/MyOl
* This package is tested with : PhpBB 3.2.7

INSTALLATION
============
* Prerequisites:
	- Hosting PHP 5.4 and above & MySQL 5.7 and above

* Install a PhpBB 3.2.x forum:
	- Download the [complete pack] (http://www.phpbb-fr.com/telechargements/)
	- Unzip and transfer it to the server.
	To learn more: [doc install PhpBB] (https://www.phpbb.com/community/docs/INSTALL.html)
	- Create an empty MySQL database or use an existing database.
	- Go to the root of the forum from an explorer, follow the instructions.
	- To learn more: [Forum Documentation] (https://www.phpbb.com/support/docs/en/3.2/ug/)

* Install GeoBB:
	- Download this extension ("Download ZIP" button above)
	- Unzip and transfer to the root of the forum.
	- Go to the forum administration (Link at the bottom of the forum) => PERSONALIZE => Manage extensions => GeoBB => Activate
	- FORUMS => Manage Forums => Create a new forum
	- Copy permissions from: => Your first forum
	- Forum Name: The name of the type of points that will be in this forum.
	- Forum image: The icon URL that will represent the points of this forum (optional).
	These icons (.png 16x16 files) are to be transferred to any directory of the server.
		- Example: ```ext/Dominique92/GeoBB/types_points/site.png```
	- Description: Insert one of the following strings:
		- ```[first=point]``` if you want to associate a position to each topic of the forum (in fact to the first comment of each subject).
		- ```[first=line]``` if you want to associate lines with the first comment of each topic.
		- ```[first=surface]``` if you want to associate surfaces with the first comment of each topic.
	- Submit

* Create a point:
	- Go to the website => In the new forum => New subject
	- Enter the name, comment, drag the yellow cursor on the map to set the position.
	- Submit

* Customization:
(optional, for developer)
	- Style:
	- [The basics of styles] (https://www.phpbb.com/styles/installing/)
	- [downloadable PhpBB 3.2 Styles] (https://www.phpbb.com/customise/db/styles/board_styles-12/3.2?sk=r&sd=d)
	- [Editing and creating styles] (https://www.phpbb.com/styles/create/)
	- Features:
	- [The extensions] (https://www.phpbb.com/extensions/)
	- [Downloadable PhpBB 3.2 Extensions] (https://www.phpbb.com/customise/db/extensions-36/3.2?sk=r&sd=d)
	- [Develop an extension] (https://www.phpbb.com/extensions/writing/)
	- Display of maps:
	- The map display is realized by a library based on Openlayers, a number of plugins and optimizations whose sources are available [HERE] (https://github.com/Dominique92/MyOl)